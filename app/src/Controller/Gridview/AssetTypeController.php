<?php

namespace App\Controller\Gridview;

use App\Entity\Asset\AssetType;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/asset-type', name: 'gridview_asset_type_')]
class AssetTypeController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return AssetType::class;
    }

    protected function viewConfig(): array
    {
        return [
            'form' => ['theme' => null], // repara-demo sets a global twig.form_themes (bootstrap_5_horizontal_layout.html.twig); defer to it instead of the bundle's own gv_form_theme.html.twig default.
            'id'       => 'asset_type',
            'title'    => 'Tipo asset',
            'addLabel' => 'Nuovo tipo asset',
            'exportFilename' => 'tipi-asset',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => AssetType::class,
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
            [
                'attribute' => 'slug',
                'label' => 'Slug',
                'filter' => ['type' => 'text'],
                // Auto-generated from name (Gedmo); editable only when updating.
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
