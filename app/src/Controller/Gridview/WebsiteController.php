<?php

namespace App\Controller\Gridview;

use App\Entity\Website\Website;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/website', name: 'gridview_website_')]
class WebsiteController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return Website::class;
    }

    protected function viewConfig(): array
    {
        return [
            'form' => ['theme' => null], // repara-demo sets a global twig.form_themes (bootstrap_5_horizontal_layout.html.twig); defer to it instead of the bundle's own gv_form_theme.html.twig default.
            'id'       => 'website',
            'title'    => 'Sito',
            'addLabel' => 'Nuovo sito',
            'exportFilename' => 'siti',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => Website::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'map' => [
                    'id'   => ['asc' => ['w.id'],   'desc' => ['w.id'],   'default' => 'desc'],
                    'name' => ['asc' => ['w.name'], 'desc' => ['w.name'], 'default' => 'asc'],
                    'sort' => ['asc' => ['w.sort'], 'desc' => ['w.sort'], 'default' => 'asc'],
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
                'control' => ['type' => 'text', 'required' => true, 'requiredMessage' => 'Il nome è obbligatorio.'],
            ],
            [
                'attribute' => 'code',
                'label' => 'Codice',
                'filter' => ['type' => 'text'],
                'editable' => true,
                'control' => [
                    'type' => 'text',
                    'required' => true,
                    'requiredMessage' => 'Il codice è obbligatorio.',
                    'unique' => true,
                    'uniqueMessage' => 'Esiste già un sito con questo codice.',
                ],
            ],
            [
                'attribute' => 'defaultGroupId',
                'label' => 'Gruppo default',
                'type' => 'number',
                'control' => ['type' => 'number', 'required' => true],
            ],
            [
                'attribute' => 'sort',
                'label' => 'Ordine',
                'type' => 'number',
                'control' => ['type' => 'number', 'required' => true],
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
