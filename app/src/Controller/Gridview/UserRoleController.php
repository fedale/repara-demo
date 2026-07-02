<?php

namespace App\Controller\Gridview;

use App\Entity\User\UserRole;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/user-role', name: 'gridview_user_role_')]
class UserRoleController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return UserRole::class;
    }

    protected function viewConfig(): array
    {
        return [
            'id'       => 'user_role',
            'title'    => 'Ruolo',
            'addLabel' => 'Nuovo ruolo',
            'exportFilename' => 'ruoli',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => UserRole::class,
            'pagination' => ['defaultPageSize' => 20],
            // Self-referencing ManyToMany (parents/children) + inverse users:
            // keep them out of normalization to avoid circular references. The
            // 'children' control still binds directly to the entity in the form.
            'ignoredAttributes' => ['users', 'parents', 'children'],
            'sort' => [
                'map' => [
                    'id'   => ['asc' => ['r.id'],   'desc' => ['r.id'],   'default' => 'desc'],
                    'name' => ['asc' => ['r.name'], 'desc' => ['r.name'], 'default' => 'asc'],
                    'code' => ['asc' => ['r.code'], 'desc' => ['r.code'], 'default' => 'asc'],
                ],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        return [
            'id',
            [
                'attribute' => 'name',
                'label' => 'Nome',
                'filter' => ['type' => 'text'],
                'editable' => true,
                'control' => ['type' => 'text', 'required' => true, 'requiredMessage' => 'Il nome è obbligatorio.'],
            ],
            [
                'attribute' => 'code',
                'label' => 'Codice',
                'filter' => ['type' => 'text'],
                'editable' => true,
                'control' => [
                    'type' => 'text',
                    'required' => true,
                    'requiredMessage' => 'Il codice è obbligatorio.',
                    'unique' => true,
                    'uniqueMessage' => 'Esiste già un ruolo con questo codice.',
                ],
            ],
            [
                'attribute' => 'slug',
                'label' => 'Slug',
                'filter' => ['type' => 'text'],
                'control' => ['type' => 'text', 'modes' => ['edit'], 'required' => false],
            ],
            // Hierarchy: 'children' is the owning side, so the relation control
            // persists the join table directly. Form-only (not a grid column).
            [
                'attribute' => 'children',
                'label' => 'Ruoli figli',
                'visible' => false,
                'sortable' => false,
                'filterable' => false,
                'type' => 'relation',
                'control' => ['options' => ['class' => UserRole::class, 'choice_label' => 'name', 'multiple' => true]],
            ],
            ['type' => 'action', 'label' => 'Azioni'],
        ];
    }
}
