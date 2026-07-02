<?php

namespace App\Controller\Gridview;

use App\Entity\Customer\Customer;
use App\Entity\Customer\CustomerLocation;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/customer-location', name: 'gridview_customer_location_')]
class CustomerLocationController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return CustomerLocation::class;
    }

    protected function viewConfig(): array
    {
        return [
            'id'       => 'customer_location',
            'title'    => 'Sede cliente',
            'addLabel' => 'Nuova sede',
            'exportFilename' => 'sedi-cliente',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => CustomerLocation::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'map' => [
                    'id'   => ['asc' => ['l.id'],   'desc' => ['l.id'],   'default' => 'desc'],
                    'name' => ['asc' => ['l.name'], 'desc' => ['l.name'], 'default' => 'asc'],
                    'city' => ['asc' => ['l.city'], 'desc' => ['l.city'], 'default' => 'asc'],
                ],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        $customerChoices = [];
        foreach ($this->em()->getRepository(Customer::class)->findAll() as $customer) {
            $customerChoices[$customer->getCode()] = $customer->getId();
        }

        return [
            'id',
            [
                'attribute' => 'customer',
                'label' => 'Cliente',
                'type' => 'relation',
                'value' => fn(array $data) => $data['customer']['code'] ?? '—',
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $customerChoices, 'multiple' => true, 'searchable' => true],
                ],
                'control' => ['options' => ['class' => Customer::class, 'choice_label' => 'code']],
            ],
            [
                'attribute' => 'name',
                'label' => 'Nome',
                'filter' => ['type' => 'text'],
                'editable' => true,
                'control' => ['type' => 'text', 'required' => true, 'requiredMessage' => 'Il nome è obbligatorio.'],
            ],
            [
                'attribute' => 'address',
                'label' => 'Indirizzo',
                'filter' => ['type' => 'text'],
                'control' => ['type' => 'text', 'required' => true],
            ],
            ['attribute' => 'zipcode', 'label' => 'CAP', 'control' => ['type' => 'text', 'required' => false]],
            [
                'attribute' => 'city',
                'label' => 'Città',
                'filter' => ['type' => 'text'],
                'editable' => true,
                'control' => ['type' => 'text', 'required' => true],
            ],
            ['attribute' => 'country', 'label' => 'Paese', 'control' => ['type' => 'text', 'required' => true]],
            [
                'attribute' => 'active',
                'label' => 'Attivo',
                'type' => 'boolean',
                'filter' => ['type' => 'boolean'],
                'batchUpdate' => true,
                'editable' => true,
                'control' => ['type' => 'boolean', 'required' => false],
            ],
            ['type' => 'action', 'label' => 'Azioni'],
        ];
    }
}
