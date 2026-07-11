<?php

namespace App\Controller\Gridview;

use App\Entity\Customer\CustomerLocation;
use App\Entity\Customer\CustomerLocationPlace;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/customer-location-place', name: 'gridview_customer_location_place_')]
class CustomerLocationPlaceController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return CustomerLocationPlace::class;
    }

    protected function viewConfig(): array
    {
        return [
            'form' => ['theme' => null], // repara-demo sets a global twig.form_themes (bootstrap_5_horizontal_layout.html.twig); defer to it instead of the bundle's own gv_form_theme.html.twig default.
            'id'       => 'customer_location_place',
            'title'    => 'Locale sede',
            'addLabel' => 'Nuovo locale',
            'exportFilename' => 'locali-sede',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => CustomerLocationPlace::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'map' => [
                    'id'   => ['asc' => ['p.id'],   'desc' => ['p.id'],   'default' => 'desc'],
                    'name' => ['asc' => ['p.name'], 'desc' => ['p.name'], 'default' => 'asc'],
                ],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        $locationChoices = [];
        foreach ($this->em()->getRepository(CustomerLocation::class)->findAll() as $location) {
            $locationChoices[$location->getCity() . ' — ' . $location->getName()] = $location->getId();
        }

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
                'attribute' => 'customerLocation',
                'label' => 'Sede',
                'type' => 'relation',
                'value' => fn(array $data) => $data['customerLocation']['name'] ?? '—',
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $locationChoices, 'multiple' => true, 'searchable' => true],
                ],
                'control' => ['options' => ['class' => CustomerLocation::class, 'choice_label' => 'name', 'required' => false]],
            ],
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
