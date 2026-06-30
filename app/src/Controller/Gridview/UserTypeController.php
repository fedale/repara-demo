<?php

namespace App\Controller\Gridview;

use App\Entity\User\UserType;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/user-type', name: 'gridview_user_type_')]
class UserTypeController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return UserType::class;
    }

    protected function viewConfig(): array
    {
        return [
            'id'       => 'user_type',
            'title'    => 'Tipo utente',
            'addLabel' => 'Nuovo tipo utente',
            'exportFilename' => 'tipi-utente',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'models' => UserType::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'id'   => ['asc' => ['t.id'],   'desc' => ['t.id'],   'default' => 'desc'],
                'name' => ['asc' => ['t.name'], 'desc' => ['t.name'], 'default' => 'asc'],
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
