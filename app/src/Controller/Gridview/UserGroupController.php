<?php

namespace App\Controller\Gridview;

use App\Entity\User\UserGroup;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/user-group', name: 'gridview_user_group_')]
class UserGroupController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return UserGroup::class;
    }

    protected function viewConfig(): array
    {
        return [
            'form' => ['theme' => null], // repara-demo sets a global twig.form_themes (bootstrap_5_horizontal_layout.html.twig); defer to it instead of the bundle's own gv_form_theme.html.twig default.
            'id'       => 'user_group',
            'title'    => 'Gruppo utenti',
            'addLabel' => 'Nuovo gruppo',
            'exportFilename' => 'gruppi-utenti',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => UserGroup::class,
            'pagination' => ['defaultPageSize' => 20],
            // 'users' is the inverse side of a ManyToMany; its getter is used for
            // display only (membership is edited from the User grid), so keep it
            // out of the serializer to avoid eager-loading the whole relation.
            'ignoredAttributes' => ['users'],
            'sort' => [
                'map' => [
                    'id'   => ['asc' => ['g.id'],   'desc' => ['g.id'],   'default' => 'desc'],
                    'name' => ['asc' => ['g.name'], 'desc' => ['g.name'], 'default' => 'asc'],
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
                'attribute' => 'slug',
                'label' => 'Slug',
                'filter' => ['type' => 'text'],
                'control' => ['type' => 'text', 'modes' => ['edit'], 'required' => false],
            ],
            ['type' => 'action', 'label' => 'Azioni'],
        ];
    }
}
