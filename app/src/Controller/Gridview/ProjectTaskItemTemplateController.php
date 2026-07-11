<?php

namespace App\Controller\Gridview;

use App\Entity\Project\Task\ProjectTaskType;
use App\Entity\Project\TaskTemplate\ProjectTaskItemTemplate;
use App\Entity\Project\TaskTemplate\ProjectTaskTemplate;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/project-task-item-template', name: 'gridview_project_task_item_template_')]
class ProjectTaskItemTemplateController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return ProjectTaskItemTemplate::class;
    }

    protected function viewConfig(): array
    {
        return [
            'form' => ['theme' => null], // repara-demo sets a global twig.form_themes (bootstrap_5_horizontal_layout.html.twig); defer to it instead of the bundle's own gv_form_theme.html.twig default.
            'id'       => 'project_task_item_template',
            'title'    => 'Voce template',
            'addLabel' => 'Nuova voce',
            'exportFilename' => 'voci-template',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => ProjectTaskItemTemplate::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'map' => [
                    'id'   => ['asc' => ['i.id'],   'desc' => ['i.id'],   'default' => 'desc'],
                    'name' => ['asc' => ['i.name'], 'desc' => ['i.name'], 'default' => 'asc'],
                    'sort' => ['asc' => ['i.sort'], 'desc' => ['i.sort'], 'default' => 'asc'],
                ],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        $templateChoices = [];
        foreach ($this->em()->getRepository(ProjectTaskTemplate::class)->findAll() as $tpl) {
            $templateChoices[$tpl->getName()] = $tpl->getId();
        }

        $typeChoices = [];
        foreach ($this->em()->getRepository(ProjectTaskType::class)->findAll() as $type) {
            $typeChoices[$type->getName()] = $type->getId();
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
                'attribute' => 'taskTemplate',
                'label' => 'Template',
                'type' => 'relation',
                'value' => fn(array $data) => $data['taskTemplate']['name'] ?? '—',
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $templateChoices, 'multiple' => true, 'searchable' => true],
                ],
                'control' => ['options' => ['class' => ProjectTaskTemplate::class, 'choice_label' => 'name', 'required' => false]],
            ],
            [
                'attribute' => 'taskType',
                'label' => 'Tipo attività',
                'type' => 'relation',
                'value' => fn(array $data) => $data['taskType']['name'] ?? '—',
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $typeChoices, 'multiple' => true, 'searchable' => true],
                ],
                'control' => ['options' => ['class' => ProjectTaskType::class, 'choice_label' => 'name', 'required' => false]],
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
