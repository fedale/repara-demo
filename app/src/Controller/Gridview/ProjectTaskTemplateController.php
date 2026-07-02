<?php

namespace App\Controller\Gridview;

use App\Entity\Project\TaskTemplate\ProjectTaskTemplate;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/project-task-template', name: 'gridview_project_task_template_')]
class ProjectTaskTemplateController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return ProjectTaskTemplate::class;
    }

    protected function viewConfig(): array
    {
        return [
            'id'       => 'project_task_template',
            'title'    => 'Template attività',
            'addLabel' => 'Nuovo template',
            'exportFilename' => 'template-attivita',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => ProjectTaskTemplate::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'map' => [
                    'id'   => ['asc' => ['tt.id'],   'desc' => ['tt.id'],   'default' => 'desc'],
                    'name' => ['asc' => ['tt.name'], 'desc' => ['tt.name'], 'default' => 'asc'],
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
                'attribute' => 'description',
                'label' => 'Descrizione',
                'control' => ['type' => 'html', 'required' => false],
            ],
            // Items are managed via their own grid (Voci template); shown here as a count.
            [
                'attribute' => 'items',
                'label' => 'Voci',
                'sortable' => false,
                'filterable' => false,
                'value' => fn(array $data) => \count($data['items'] ?? []),
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
