<?php

namespace App\Controller\Gridview;

use App\Entity\Project\Task\ProjectTask;
use App\Entity\Project\TaskItem\ProjectTaskItem;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/project-task-item', name: 'gridview_project_task_item_')]
class ProjectTaskItemController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return ProjectTaskItem::class;
    }

    protected function viewConfig(): array
    {
        return [
            'form' => ['theme' => null], // repara-demo sets a global twig.form_themes (bootstrap_5_horizontal_layout.html.twig); defer to it instead of the bundle's own gv_form_theme.html.twig default.
            'id'       => 'project_task_item',
            'title'    => 'Voce attività',
            'addLabel' => 'Nuova voce',
            'exportFilename' => 'voci-attivita',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => ProjectTaskItem::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'map' => [
                    'id'   => ['asc' => ['i.id'],   'desc' => ['i.id'],   'default' => 'desc'],
                    'name' => ['asc' => ['i.name'], 'desc' => ['i.name'], 'default' => 'asc'],
                ],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        $taskChoices = [];
        foreach ($this->em()->getRepository(ProjectTask::class)->findAll() as $task) {
            $taskChoices[$task->getName()] = $task->getId();
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
                'attribute' => 'projectTask',
                'label' => 'Attività',
                'type' => 'relation',
                'value' => fn(array $data) => $data['projectTask']['name'] ?? '—',
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $taskChoices, 'multiple' => true, 'searchable' => true],
                ],
                'control' => ['options' => ['class' => ProjectTask::class, 'choice_label' => 'name', 'required' => true]],
            ],
            [
                'attribute' => 'description',
                'label' => 'Descrizione',
                'visible' => false,
                'control' => ['type' => 'html', 'modes' => ['edit'], 'required' => false],
            ],
            [
                'attribute' => 'difficulty',
                'label' => 'Difficoltà',
                'type' => 'number',
                'editable' => true,
                'control' => ['type' => 'number', 'required' => true],
            ],
            [
                'attribute' => 'value',
                'label' => 'Valore',
                'filter' => ['type' => 'text'],
                'editable' => true,
                'control' => ['type' => 'text', 'required' => false],
            ],
            [
                'attribute' => 'datetimeStart',
                'label' => 'Inizio',
                'type' => 'date',
                'control' => ['type' => 'date', 'required' => false, 'options' => ['widget' => 'single_text']],
            ],
            [
                'attribute' => 'datetimeEnd',
                'label' => 'Fine',
                'type' => 'date',
                'control' => ['type' => 'date', 'required' => false, 'options' => ['widget' => 'single_text']],
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
