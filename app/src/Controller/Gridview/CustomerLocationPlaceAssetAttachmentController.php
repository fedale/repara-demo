<?php

namespace App\Controller\Gridview;

use App\Entity\Customer\CustomerLocationPlaceAsset;
use App\Entity\Customer\CustomerLocationPlaceAssetAttachment;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/customer-location-place-asset-attachment', name: 'gridview_customer_location_place_asset_attachment_')]
class CustomerLocationPlaceAssetAttachmentController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return CustomerLocationPlaceAssetAttachment::class;
    }

    protected function viewConfig(): array
    {
        return [
            'id'       => 'customer_location_place_asset_attachment',
            'title'    => 'Allegato asset installato',
            'addLabel' => 'Nuovo allegato',
            'exportFilename' => 'allegati-asset-installati',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'models' => CustomerLocationPlaceAssetAttachment::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'id'   => ['asc' => ['a.id'],   'desc' => ['a.id'],   'default' => 'desc'],
                'name' => ['asc' => ['a.name'], 'desc' => ['a.name'], 'default' => 'asc'],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        $assetChoices = [];
        foreach ($this->em()->getRepository(CustomerLocationPlaceAsset::class)->findAll() as $cpa) {
            $assetChoices[$cpa->getName()] = $cpa->getId();
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
                'attribute' => 'customerLocationPlaceAsset',
                'label' => 'Asset installato',
                'type' => 'relation',
                'value' => fn(array $data) => $data['customerLocationPlaceAsset']['name'] ?? '—',
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $assetChoices, 'multiple' => true, 'searchable' => true],
                ],
                'control' => ['options' => ['class' => CustomerLocationPlaceAsset::class, 'choice_label' => 'name', 'required' => false]],
            ],
            [
                'attribute' => 'type',
                'label' => 'Tipo',
                'filter' => ['type' => 'text'],
                'editable' => true,
                'control' => ['type' => 'text', 'required' => true],
            ],
            ['attribute' => 'filename', 'label' => 'File', 'control' => ['type' => 'text', 'required' => true]],
            ['attribute' => 'path', 'label' => 'Percorso', 'visible' => false, 'control' => ['type' => 'text', 'required' => true]],
            ['attribute' => 'size', 'label' => 'Dimensione', 'type' => 'number', 'control' => ['type' => 'number', 'required' => true]],
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
