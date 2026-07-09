# https://docs.docker.com/engine/reference/builder/#understand-how-arg-and-from-interact
ARG PHP_VERSION=8.3
ARG CADDY_VERSION=2

FROM php:${PHP_VERSION}-fpm AS app

ARG APCU_VERSION=5.1.24
ARG APP_ENV=prod

RUN apt update \
    && apt install -y zlib1g-dev g++ git libpq-dev libicu-dev zip libzip-dev zip acl apt-transport-https gnupg apt-utils \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev fonts-dejavu-core \
        libfcgi-bin \
    && docker-php-ext-install intl opcache pdo pdo_mysql pdo_pgsql \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && pecl install apcu \
    && pecl install redis \
    && pecl clear-cache \
    && docker-php-ext-enable pdo_pgsql \
    && docker-php-ext-enable apcu \
    && docker-php-ext-enable opcache \
    && docker-php-ext-enable redis \
    && docker-php-ext-install zip \
    && docker-php-ext-configure pgsql --with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pgsql

WORKDIR /srv/app

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
RUN apt-get install symfony-cli
RUN git config --global user.email "zitter@gmail.com" \
    && git config --global user.name "Fedale"

# RUN ln -s $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini
# COPY nginx/php/conf.d/api-platform.prod.ini $PHP_INI_DIR/conf.d/api-platform.ini

# COPY nginx/php/php-fpm.d/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

# # copy only specifically what we need
COPY app/.env ./
COPY app/bin bin/
COPY app/config config/
COPY app/migrations migrations/
COPY app/public public/
COPY app/src src/
COPY app/templates templates/
COPY app/translations translations/

# local repo for local dev
# NOTE: gridview-bundle ora vive in un repo separato (sibling di repara-demo/) ed e'
# pubblicato su Packagist. NON e' nel build context: in PROD viene risolto da Packagist
# (require "^1.0"), in DEV via volume mount + repository type:path con symlink (vedi
# app/composer.json e docker-compose.yml: ../gridview-bundle:/srv/gridview-bundle).

# prevent the reinstallation of vendors at every changes in the source code
COPY app/composer.json app/composer.lock app/symfony.lock ./

# Build di PRODUZIONE: i repository type:path dei bundle fedale pubblicati su
# Packagist (gridview, access-control-voter) usano una url glob con graffe
# "../{nome-bundle}". ATTENZIONE: un path repo con url NON-glob inesistente fa
# fallire Composer con un fatal ("The `url` supplied for the path (...) does not
# exist") — NON viene ignorato. La graffa rende il glob tollerante quando la dir
# e' assente (caso prod/build context), cosi' Composer risolve da Packagist (^1.0).
# Si usa "composer update" (non "install") perche' il composer.lock committato e'
# quello di dev e referenzia il dist path locale; update ri-risolve le dipendenze
# contro Packagist e rigenera il lock dentro l'immagine.
RUN set -eux; \
	mkdir -p var/cache var/log; \
	composer update --no-dev --prefer-dist --no-progress; \
	composer dump-autoload --classmap-authoritative --no-dev; \
	composer dump-env prod; \
	composer run-script --no-dev post-install-cmd; \
	chmod +x bin/console; \
	composer clear-cache; \
	sync

COPY nginx/php/docker-healthcheck.sh /usr/local/bin/docker-healthcheck
RUN chmod +x /usr/local/bin/docker-healthcheck

HEALTHCHECK --interval=10s --timeout=3s --retries=3 CMD ["docker-healthcheck"]

COPY nginx/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini

ENV SYMFONY_PHPUNIT_VERSION=9

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]


# "database" stage
FROM postgres:${POSTGRES_VERSION:-14.5}-bullseye AS database

EXPOSE 5432


# "nginx" stage
FROM nginx:${NGINX_VERSION:-1.23} AS nginx
RUN apt update && apt install -y vim openssl
RUN rm -rfv /etc/conf.d/default.conf
COPY app/public /srv/app/public
COPY nginx/config/nginx.conf /etc/nginx/nginx.conf
COPY nginx/config/app.conf /etc/nginx/conf.d/app.conf
RUN openssl req -x509 -nodes -days 365 -subj "/C=CA/ST=QC/O=Company, Inc./CN=mydomain.com" -addext "subjectAltName=DNS:mydomain.com" -newkey rsa:2048 -keyout /etc/ssl/private/nginx-selfsigned.key -out /etc/ssl/certs/nginx-selfsigned.crt;
EXPOSE 80:443
ENTRYPOINT nginx -g 'daemon off;'

# "caddy" stage
# depends on the "php" stage above
FROM caddy:${CADDY_VERSION}-builder-alpine AS api_caddy_builder
RUN xcaddy build \
    --with github.com/dunglas/mercure \
    --with github.com/dunglas/mercure/caddy \
    --with github.com/dunglas/vulcain \
    --with github.com/dunglas/vulcain/caddy

#'caddy' stage
FROM caddy:${CADDY_VERSION} AS caddy
WORKDIR /srv/api
COPY --from=dunglas/mercure:v0.11 /srv/public /srv/mercure-assets/
COPY --from=api_caddy_builder /usr/bin/caddy /usr/bin/caddy
COPY --from=api /srv/api/public public/
COPY caddy/Caddyfile /etc/caddy/Caddyfile
