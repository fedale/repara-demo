<?php

namespace App\Controller\Gridview;

use App\Entity\Asset\AssetCategory;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/asset-category', name: 'gridview_asset_category_')]
class AssetCategoryController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return AssetCategory::class;
    }

    protected function viewConfig(): array
    {
        return [
            'id'       => 'asset_category',
            'title'    => 'Categoria asset',
            'addLabel' => 'Nuova categoria',
            'exportFilename' => 'categorie-asset',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => AssetCategory::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'map' => [
                    'id'   => ['asc' => ['c.id'],   'desc' => ['c.id'],   'default' => 'desc'],
                    'name' => ['asc' => ['c.name'], 'desc' => ['c.name'], 'default' => 'asc'],
                ],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        $parentChoices = [];
        foreach ($this->em()->getRepository(AssetCategory::class)->findAll() as $cat) {
            $parentChoices[$cat->getName()] = $cat->getId();
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
                'attribute' => 'parent',
                'label' => 'Categoria padre',
                'type' => 'relation',
                'value' => fn(array $data) => $data['parent']['name'] ?? '—',
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $parentChoices, 'multiple' => true, 'searchable' => true],
                ],
                'control' => ['options' => ['class' => AssetCategory::class, 'choice_label' => 'name', 'required' => false]],
            ],
            // Tree bounds — display only (managed by Gedmo), not editable.
            ['attribute' => 'lft', 'label' => 'Lft', 'type' => 'number', 'sortable' => false],
            ['attribute' => 'rgt', 'label' => 'Rgt', 'type' => 'number', 'sortable' => false],
            ['attribute' => 'root', 'label' => 'Root', 'type' => 'number', 'sortable' => false],
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
