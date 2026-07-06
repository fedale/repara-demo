<?php

namespace App\Command;

use App\Repository\User\UserRepository;
use Fedale\RbacBundle\Contract\AccessManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Tests can() for a real user by SIMULATING their token: unlike fedale:rbac:check
 * (static, auth_assignment only), it exercises the whole path — direct
 * assignments + token roles + hierarchy + rules + super-admin.
 *
 *   bin/console rbac:user:can massimo EDIT_INVOICE
 */
#[AsCommand(
    name: 'rbac:user:can',
    description: 'Test AccessManager::can() for a user, simulating their token',
)]
final class RbacCanCommand extends Command
{
    public function __construct(
        private readonly AccessManagerInterface $accessManager,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UserRepository $users,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Username (or email)');
        $this->addArgument('permission', InputArgument::REQUIRED, 'Item to check (role or permission)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = (string) $input->getArgument('username');
        $permission = (string) $input->getArgument('permission');

        // Note: use findOneBy (not UserRepository::loadUserByIdentifier, which in
        // this demo generates broken SQL on user_role).
        $user = $this->users->findOneBy(['username' => $username]);

        if (null === $user) {
            $io->error(sprintf('User "%s" not found.', $username));

            return Command::FAILURE;
        }

        // Simulate the user's authenticated token (same firewall: main).
        $this->tokenStorage->setToken(
            new UsernamePasswordToken($user, 'main', $user->getRoles()),
        );

        $granted = $this->accessManager->can($permission);

        $io->writeln(sprintf('user:    <info>%s</>', $user->getUserIdentifier()));
        $io->writeln(sprintf('roles:   %s', implode(', ', $user->getRoles())));
        $io->writeln(sprintf('can(%s): <%s>%s</>',
            $permission,
            $granted ? 'info' : 'comment',
            $granted ? 'GRANTED' : 'DENIED',
        ));

        return Command::SUCCESS;
    }
}
