<?php

namespace App\Controller\Gridview;

use Fedale\AccessControlBundle\Bridge\Doctrine\Entity\AccessControlEntity;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * CRUD grid for the access-control rules (table `access_control`, entity owned
 * by fedale/access-control-bundle). The bundle repository doesn't implement the
 * grid's search() contract, so the data provider builds a fallback QueryBuilder
 * from `alias`/`searchFields` below.
 *
 * Reminder: with default_policy: deny the table must keep at least one
 * permissive rule, and the rule cache (cache.app) must be cleared after edits —
 * `php bin/console cache:pool:clear cache.app`.
 */
#[Route('/gridview/access-control', name: 'gridview_access_control_')]
class AccessControlController extends AbstractCrudGridController
{
    /** HTTP verbs offered by the `methods` control (empty = any method). */
    private const HTTP_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    protected function getDataClass(): string
    {
        return AccessControlEntity::class;
    }

    protected function viewConfig(): array
    {
        return [
            'id' => 'access_control',
            'title' => 'Regola di accesso',
            'addLabel' => 'Nuova regola',
            'exportFilename' => 'access-control',
            'options' => [
                'reorderColumns' => true,
            ],
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'models' => AccessControlEntity::class,
            'alias' => 'ac',
            'pagination' => ['defaultPageSize' => 20],
            // Applied to the fallback QueryBuilder (bundle repo has no search()).
            'searchFields' => [
                'name' => ['text', 'ac.name'],
                'path' => ['text', 'ac.path'],
                'host' => ['text', 'ac.host'],
                'active' => ['boolean', 'ac.active'],
                'allow' => ['boolean', 'ac.allow'],
            ],
            'sort' => [
                'id' => ['asc' => ['ac.id'], 'desc' => ['ac.id'], 'default' => 'desc'],
                'name' => ['asc' => ['ac.name'], 'desc' => ['ac.name'], 'default' => 'asc'],
                'path' => ['asc' => ['ac.path'], 'desc' => ['ac.path'], 'default' => 'asc'],
                'sort' => ['asc' => ['ac.sort'], 'desc' => ['ac.sort'], 'default' => 'asc'],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        $methodChoices = array_combine(self::HTTP_METHODS, self::HTTP_METHODS);

        return [
            ['type' => 'checkbox'],
            'id',
            [
                'attribute' => 'name',
                'label' => 'Nome',
                'filter' => ['type' => 'text'],
                'editable' => true,
                'showInDeleteConfirm' => true,
                'control' => [
                    'type' => 'text',
                    'required' => true,
                    'requiredMessage' => 'Il nome è obbligatorio.',
                ],
            ],
            [
                'attribute' => 'path',
                'label' => 'Path (regex)',
                'filter' => ['type' => 'text'],
                'editable' => true,
                'showInDeleteConfirm' => true,
                'control' => [
                    'type' => 'text',
                    'required' => true,
                    'requiredMessage' => 'Il path è obbligatorio.',
                    'options' => ['help' => 'Espressione regolare confrontata col path, es. ^/admin'],
                ],
            ],
            [
                'attribute' => 'host',
                'label' => 'Host',
                'value' => fn(array $data) => $data['host'] ?: '—',
                'filter' => ['type' => 'text'],
                'control' => ['type' => 'text', 'required' => false],
            ],
            // roles/ips: JSON string arrays — bound to a comma-separated text
            // field through getter/setter so they stay free-form.
            [
                'attribute' => 'roles',
                'label' => 'Ruoli',
                'value' => fn(array $data) => implode(', ', $data['roles'] ?? []) ?: '—',
                'sortable' => false,
                'control' => [
                    'type' => 'text',
                    'required' => false,
                    'options' => [
                        'help' => 'Ruoli separati da virgola, es. ROLE_Admin, PUBLIC_ACCESS',
                        'getter' => fn(AccessControlEntity $e) => implode(', ', $e->getRoles()),
                        'setter' => fn(AccessControlEntity $e, ?string $v) => $e->setRoles(self::splitList($v)),
                    ],
                ],
            ],
            [
                'attribute' => 'ips',
                'label' => 'IP',
                'value' => fn(array $data) => implode(', ', $data['ips'] ?? []) ?: '—',
                'sortable' => false,
                'control' => [
                    'type' => 'text',
                    'required' => false,
                    'options' => [
                        'help' => 'Indirizzi/CIDR separati da virgola, es. 127.0.0.1, 10.0.0.0/8',
                        'getter' => fn(AccessControlEntity $e) => implode(', ', $e->getIps()),
                        'setter' => fn(AccessControlEntity $e, ?string $v) => $e->setIps(self::splitList($v)),
                    ],
                ],
            ],
            // methods: fixed HTTP verb set — multiple choice.
            [
                'attribute' => 'methods',
                'label' => 'Metodi',
                'type' => 'choice',
                'value' => fn(array $data) => implode(', ', $data['methods'] ?? []) ?: 'Tutti',
                'sortable' => false,
                'control' => [
                    'type' => 'choice',
                    'required' => false,
                    'options' => [
                        'choices' => $methodChoices,
                        'multiple' => true,
                        'expanded' => false,
                        'help' => 'Vuoto = tutti i metodi',
                    ],
                ],
            ],
            [
                'attribute' => 'allow',
                'label' => 'Consenti',
                'type' => 'boolean',
                'filter' => true,
                'batchUpdate' => true,
                'editable' => true,
                'showInDeleteConfirm' => true,
                'control' => ['required' => false],
            ],
            [
                'attribute' => 'sort',
                'label' => 'Ordine',
                'editable' => true,
                'control' => ['type' => 'number', 'required' => false],
            ],
            [
                'attribute' => 'active',
                'label' => 'Attiva',
                'type' => 'boolean',
                'filter' => true,
                'batchUpdate' => true,
                'editable' => true,
                'control' => ['required' => false],
            ],
            [
                'attribute' => 'reason',
                'label' => 'Motivo',
                'visible' => false,
                'sortable' => false,
                'filterable' => false,
                'control' => ['type' => 'text', 'required' => false],
            ],
            [
                'attribute' => 'createdAt',
                'label' => 'Creata il',
                'type' => 'date',
                'twigFilter' => "date('d/m/Y H:i')",
            ],
            ['type' => 'action', 'label' => 'Azioni'],
        ];
    }

    /**
     * Splits a comma/newline separated string into a trimmed, non-empty list.
     *
     * @return string[]
     */
    private static function splitList(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            'trim',
            preg_split('/[,\n]+/', $value) ?: []
        ), static fn(string $v) => $v !== ''));
    }
}
