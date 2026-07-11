<?php

namespace App\Controller\Gridview;

use App\Entity\Asset\Asset;
use App\Entity\Customer\CustomerLocationPlace;
use App\Entity\Customer\CustomerLocationPlaceAsset;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/customer-location-place-asset', name: 'gridview_customer_location_place_asset_')]
class CustomerLocationPlaceAssetController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return CustomerLocationPlaceAsset::class;
    }

    protected function viewConfig(): array
    {
        return [
            'form' => ['theme' => null], // repara-demo sets a global twig.form_themes (bootstrap_5_horizontal_layout.html.twig); defer to it instead of the bundle's own gv_form_theme.html.twig default.
            'id'       => 'customer_location_place_asset',
            'title'    => 'Asset installato',
            'addLabel' => 'Nuovo asset installato',
            'exportFilename' => 'asset-installati',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => CustomerLocationPlaceAsset::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'map' => [
                    'id'   => ['asc' => ['cpa.id'],   'desc' => ['cpa.id'],   'default' => 'desc'],
                    'name' => ['asc' => ['cpa.name'], 'desc' => ['cpa.name'], 'default' => 'asc'],
                    'code' => ['asc' => ['cpa.code'], 'desc' => ['cpa.code'], 'default' => 'asc'],
                ],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        $placeChoices = [];
        foreach ($this->em()->getRepository(CustomerLocationPlace::class)->findAll() as $place) {
            $placeChoices[$place->getName()] = $place->getId();
        }

        $assetChoices = [];
        foreach ($this->em()->getRepository(Asset::class)->findAll() as $asset) {
            $assetChoices[$asset->getName()] = $asset->getId();
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
                'attribute' => 'code',
                'label' => 'Codice',
                'filter' => ['type' => 'text'],
                'editable' => true,
                'control' => ['type' => 'text', 'required' => true, 'requiredMessage' => 'Il codice è obbligatorio.'],
            ],
            [
                'attribute' => 'customerLocationPlace',
                'label' => 'Locale',
                'type' => 'relation',
                'value' => fn(array $data) => $data['customerLocationPlace']['name'] ?? '—',
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $placeChoices, 'multiple' => true, 'searchable' => true],
                ],
                'control' => ['options' => ['class' => CustomerLocationPlace::class, 'choice_label' => 'name', 'required' => false]],
            ],
            [
                'attribute' => 'asset',
                'label' => 'Asset',
                'type' => 'relation',
                'value' => fn(array $data) => $data['asset']['name'] ?? '—',
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $assetChoices, 'multiple' => true, 'searchable' => true],
                ],
                'control' => ['options' => ['class' => Asset::class, 'choice_label' => 'name', 'required' => false]],
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
