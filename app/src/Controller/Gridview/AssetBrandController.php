<?php

namespace App\Controller\Gridview;

use App\Entity\Asset\AssetBrand;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/asset-brand', name: 'gridview_asset_brand_')]
class AssetBrandController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return AssetBrand::class;
    }

    protected function viewConfig(): array
    {
        return [
            'id'       => 'asset_brand',
            'title'    => 'Marca asset',
            'addLabel' => 'Nuova marca',
            'exportFilename' => 'marche-asset',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => AssetBrand::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'map' => [
                    'id'   => ['asc' => ['b.id'],   'desc' => ['b.id'],   'default' => 'desc'],
                    'name' => ['asc' => ['b.name'], 'desc' => ['b.name'], 'default' => 'asc'],
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
            [
                'attribute' => 'slug',
                'label' => 'Slug',
                'filter' => ['type' => 'text'],
                'control' => ['type' => 'text', 'modes' => ['edit'], 'required' => false],
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
