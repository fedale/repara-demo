<?php

namespace App\Controller\Gridview;

use App\Entity\Project\Project;
use App\Entity\Project\ProjectType;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/project', name: 'gridview_project_')]
class ProjectController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return Project::class;
    }

    protected function viewConfig(): array
    {
        return [
            'form' => ['theme' => null], // repara-demo sets a global twig.form_themes (bootstrap_5_horizontal_layout.html.twig); defer to it instead of the bundle's own gv_form_theme.html.twig default.
            'id'       => 'project',
            'title'    => 'Progetto',
            'addLabel' => 'Nuovo progetto',
            'exportFilename' => 'progetti',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => Project::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'map' => [
                    'id'   => ['asc' => ['p.id'],   'desc' => ['p.id'],   'default' => 'desc'],
                    'code' => ['asc' => ['p.code'], 'desc' => ['p.code'], 'default' => 'asc'],
                    'name' => ['asc' => ['p.name'], 'desc' => ['p.name'], 'default' => 'asc'],
                ],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        $typeChoices = [];
        foreach ($this->em()->getRepository(ProjectType::class)->findAll() as $type) {
            $typeChoices[$type->getName()] = $type->getId();
        }

        return [
            'id',
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
                    'uniqueMessage' => 'Esiste già un progetto con questo codice.',
                ],
            ],
            [
                'attribute' => 'name',
                'label' => 'Nome',
                'filter' => ['type' => 'text'],
                'editable' => true,
                'control' => ['type' => 'text', 'required' => true, 'requiredMessage' => 'Il nome è obbligatorio.'],
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
                'control' => ['options' => ['class' => ProjectType::class, 'choice_label' => 'name', 'required' => false]],
            ],
            [
                'attribute' => 'status',
                'label' => 'Stato',
                'filter' => ['type' => 'text'],
                'control' => ['type' => 'text', 'required' => true],
            ],
            [
                'attribute' => 'priority',
                'label' => 'Priorità',
                'type' => 'number',
                'control' => ['type' => 'number', 'required' => true],
            ],
            [
                'attribute' => 'budget',
                'label' => 'Budget',
                'type' => 'number',
                'control' => ['type' => 'number', 'required' => false],
            ],
            ['type' => 'action', 'label' => 'Azioni'],
        ];
    }
}
