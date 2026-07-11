<?php

namespace App\Controller\Gridview;

use App\DBAL\Types\ProjectTaskPriorityType;
use App\DBAL\Types\ProjectTaskStateType;
use App\Entity\Customer\Customer;
use App\Entity\Customer\CustomerLocationPlaceAsset;
use App\Entity\Project\Task\ProjectTask;
use App\Entity\Project\Task\ProjectTaskType;
use App\Entity\User\User;
use App\Workflow\ProjectTaskWorkflow;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Fedale\GridviewBundle\Crud\CrudButton;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/project-task', name: 'gridview_project_task_')]
class ProjectTaskController extends AbstractCrudGridController
{
    public function __construct(
        private ProjectTaskWorkflow $projectTaskWorkflow,
    ) {
    }

    protected function getDataClass(): string
    {
        return ProjectTask::class;
    }

    protected function viewConfig(): array
    {
        return [
            'form' => ['theme' => null], // repara-demo sets a global twig.form_themes (bootstrap_5_horizontal_layout.html.twig); defer to it instead of the bundle's own gv_form_theme.html.twig default.
            'id'       => 'project_task',
            'title'    => 'Attività',
            'addLabel' => 'Nuova attività',
            'exportFilename' => 'attivita',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => ProjectTask::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'map' => [
                    'id'   => ['asc' => ['pt.id'],   'desc' => ['pt.id'],   'default' => 'desc'],
                    'name' => ['asc' => ['pt.name'], 'desc' => ['pt.name'], 'default' => 'asc'],
                ],
            ],
        ];
    }

    /** Applies the workflow "validate" transition to a task, then returns to the grid. */
    #[Route('/{id}/validate', name: 'validate', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function validate(int $id): Response
    {
        /** @var ProjectTask|null $task */
        $task = $this->em()->getRepository(ProjectTask::class)->find($id);
        if (!$task) {
            throw $this->createNotFoundException('Attività non trovata.');
        }

        try {
            $this->projectTaskWorkflow->validate($task);
            $this->em()->flush();
            $this->addFlash('success', "Attività {$task->getName()} validata.");
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute($this->routeName('index'));
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        $customerChoices = [];
        foreach ($this->em()->getRepository(Customer::class)->findAll() as $customer) {
            $customerChoices[$customer->getCode()] = $customer->getId();
        }

        $typeChoices = [];
        foreach ($this->em()->getRepository(ProjectTaskType::class)->findAll() as $type) {
            $typeChoices[$type->getName()] = $type->getId();
        }

        $priorityChoices = array_combine(ProjectTaskPriorityType::TYPES, ProjectTaskPriorityType::TYPES);
        $stateChoices = array_combine(ProjectTaskStateType::TYPES, ProjectTaskStateType::TYPES);

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
                'visible' => false,
                'control' => ['type' => 'html', 'modes' => ['edit'], 'required' => false],
            ],
            [
                'attribute' => 'customer',
                'label' => 'Cliente',
                'type' => 'relation',
                'value' => fn(array $data) => $data['customer']['code'] ?? '—',
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $customerChoices, 'multiple' => true, 'searchable' => true],
                ],
                'control' => ['options' => ['class' => Customer::class, 'choice_label' => 'code', 'required' => false]],
            ],
            [
                'attribute' => 'customerLocationPlaceAsset',
                'label' => 'Asset installato',
                'type' => 'relation',
                'value' => fn(array $data) => $data['customerLocationPlaceAsset']['name'] ?? '—',
                'control' => ['options' => ['class' => CustomerLocationPlaceAsset::class, 'choice_label' => 'name', 'required' => false]],
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
                'control' => ['options' => ['class' => ProjectTaskType::class, 'choice_label' => 'name', 'required' => false]],
            ],
            [
                'attribute' => 'priority',
                'label' => 'Priorità',
                'type' => 'badge',
                'filter' => ['type' => 'text'],
                'editable' => true,
                'control' => [
                    'type' => 'choice',
                    'required' => true,
                    'options' => ['choices' => $priorityChoices],
                ],
            ],
            [
                'attribute' => 'state',
                'label' => 'Stato',
                'type' => 'badge',
                'filter' => ['type' => 'text'],
                'editable' => true,
                'control' => [
                    'type' => 'choice',
                    'required' => true,
                    'options' => ['choices' => $stateChoices],
                ],
            ],
            [
                'attribute' => 'projectTaskUserAssigneds',
                'label' => 'Assegnatari',
                'type' => 'relation',
                'sortable' => false,
                'filterable' => false,
                'value' => fn(array $data) => implode(
                    ', ',
                    array_map(fn(array $u) => $u['username'] ?? '', $data['projectTaskUserAssigneds'] ?? [])
                ),
                'control' => ['options' => ['class' => User::class, 'choice_label' => 'username', 'multiple' => true]],
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
            [
                'type' => 'action',
                'label' => 'Azioni',
                'layout' => '{validate} {edit} {clone} {delete}',
                'buttons' => [
                    'validate' => function (array $row): string {
                        $task = $this->em()->getRepository(ProjectTask::class)->find($row['id']);
                        if (!$task || !$this->projectTaskWorkflow->canValidate($task)) {
                            return '';
                        }
                        $url = $this->generateUrl($this->routeName('validate'), ['id' => $row['id']]);

                        return sprintf(
                            '<a href="%s" class="btn btn-sm btn-success" title="Valida">✓</a>',
                            htmlspecialchars($url, ENT_QUOTES)
                        );
                    },
                    'edit' => fn(array $row) => CrudButton::edit(
                        $this->generateUrl($this->routeName('update'), ['id' => $row['id']]),
                        $this->config('form.mode') ?? 'modal'
                    ),
                    'clone' => fn(array $row) => CrudButton::clone(
                        $this->generateUrl($this->routeName('clone'), ['id' => $row['id']]),
                        $this->config('form.mode') ?? 'modal'
                    ),
                    'delete' => fn(array $row) => CrudButton::delete(
                        $this->generateUrl($this->routeName('delete'), ['id' => $row['id']])
                    ),
                ],
            ],
        ];
    }
}
