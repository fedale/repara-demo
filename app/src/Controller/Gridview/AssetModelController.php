<?php

namespace App\Controller\Gridview;

use App\Entity\Asset\AssetBrand;
use App\Entity\Asset\AssetModel;
use App\Entity\Asset\AssetType;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/asset-model', name: 'gridview_asset_model_')]
class AssetModelController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return AssetModel::class;
    }

    protected function viewConfig(): array
    {
        return [
            'form' => ['theme' => null], // repara-demo sets a global twig.form_themes (bootstrap_5_horizontal_layout.html.twig); defer to it instead of the bundle's own gv_form_theme.html.twig default.
            'id'       => 'asset_model',
            'title'    => 'Modello asset',
            'addLabel' => 'Nuovo modello',
            'exportFilename' => 'modelli-asset',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => AssetModel::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'map' => [
                    'id'   => ['asc' => ['m.id'],   'desc' => ['m.id'],   'default' => 'desc'],
                    'name' => ['asc' => ['m.name'], 'desc' => ['m.name'], 'default' => 'asc'],
                ],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        $brandChoices = [];
        foreach ($this->em()->getRepository(AssetBrand::class)->findAll() as $brand) {
            $brandChoices[$brand->getName()] = $brand->getId();
        }

        $typeChoices = [];
        foreach ($this->em()->getRepository(AssetType::class)->findAll() as $type) {
            $typeChoices[$type->getName()] = $type->getId();
        }

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
                'attribute' => 'brand',
                'label' => 'Marca',
                'type' => 'relation',
                'value' => fn(array $data) => $data['brand']['name'] ?? '—',
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $brandChoices, 'multiple' => true, 'searchable' => true],
                ],
                'control' => ['options' => ['class' => AssetBrand::class, 'choice_label' => 'name']],
            ],
            [
                'attribute' => 'type',
                'label' => 'Tipo',
                'type' => 'relation',
                'value' => fn(array $data) => $data['type']['name'] ?? '—',
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $typeChoices, 'multiple' => true, 'searchable' => true],
                ],
                'control' => ['options' => ['class' => AssetType::class, 'choice_label' => 'name']],
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
