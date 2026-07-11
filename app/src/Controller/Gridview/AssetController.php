<?php

namespace App\Controller\Gridview;

use App\Entity\Asset\Asset;
use App\Entity\Asset\AssetModel;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/asset', name: 'gridview_asset_')]
class AssetController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return Asset::class;
    }

    protected function viewConfig(): array
    {
        return [
            'form' => ['theme' => null], // repara-demo sets a global twig.form_themes (bootstrap_5_horizontal_layout.html.twig); defer to it instead of the bundle's own gv_form_theme.html.twig default.
            'id'       => 'asset',
            'title'    => 'Asset',
            'addLabel' => 'Nuovo asset',
            'exportFilename' => 'asset',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => Asset::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'map' => [
                    'id'   => ['asc' => ['a.id'],   'desc' => ['a.id'],   'default' => 'desc'],
                    'name' => ['asc' => ['a.name'], 'desc' => ['a.name'], 'default' => 'asc'],
                ],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        $modelChoices = [];
        foreach ($this->em()->getRepository(AssetModel::class)->findAll() as $model) {
            $modelChoices[$model->getName()] = $model->getId();
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
                'attribute' => 'model',
                'label' => 'Modello',
                'type' => 'relation',
                'value' => fn(array $data) => $data['model']['name'] ?? '—',
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $modelChoices, 'multiple' => true, 'searchable' => true],
                ],
                'control' => ['options' => ['class' => AssetModel::class, 'choice_label' => 'name']],
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
