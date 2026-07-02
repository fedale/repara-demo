<?php

namespace App\Controller\Gridview;

use App\Entity\Project\Project;
use App\Entity\Project\ProjectMilestone;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/project-milestone', name: 'gridview_project_milestone_')]
class ProjectMilestoneController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return ProjectMilestone::class;
    }

    protected function viewConfig(): array
    {
        return [
            'id'       => 'project_milestone',
            'title'    => 'Milestone',
            'addLabel' => 'Nuova milestone',
            'exportFilename' => 'milestone',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => ProjectMilestone::class,
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
        $projectChoices = [];
        foreach ($this->em()->getRepository(Project::class)->findAll() as $project) {
            $projectChoices[$project->getCode()] = $project->getId();
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
                'attribute' => 'project',
                'label' => 'Progetto',
                'type' => 'relation',
                'value' => fn(array $data) => $data['project']['code'] ?? '—',
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $projectChoices, 'multiple' => true, 'searchable' => true],
                ],
                'control' => ['options' => ['class' => Project::class, 'choice_label' => 'code', 'required' => true]],
            ],
            [
                'attribute' => 'expirationDate',
                'label' => 'Scadenza',
                'type' => 'date',
                'control' => ['type' => 'date', 'required' => true, 'options' => ['widget' => 'single_text']],
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
