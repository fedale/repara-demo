<?php

namespace App\Controller\Gridview;

use App\Entity\Customer\CustomerType;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/customer-type', name: 'gridview_customer_type_')]
class CustomerTypeController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return CustomerType::class;
    }

    protected function viewConfig(): array
    {
        return [
            'id'       => 'customer_type',
            'title'    => 'Tipo cliente',
            'addLabel' => 'Nuovo tipo cliente',
            'exportFilename' => 'tipi-cliente',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => CustomerType::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'map' => [
                    'id'   => ['asc' => ['t.id'],   'desc' => ['t.id'],   'default' => 'desc'],
                    'name' => ['asc' => ['t.name'], 'desc' => ['t.name'], 'default' => 'asc'],
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
                'control' => [
                    'type' => 'text',
                    'required' => true,
                    'requiredMessage' => 'Il nome è obbligatorio.',
                ],
            ],
            ['type' => 'action', 'label' => 'Azioni'],
        ];
    }
}
