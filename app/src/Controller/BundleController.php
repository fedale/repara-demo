<?php

namespace App\Controller;

use App\Service\CustomerSearchModel;
use Fedale\GridviewBundle\Contract\ColumnInterface;
use Fedale\GridviewBundle\Contract\GridviewBuilderInterface;
use Fedale\GridviewBundle\Grid\GridviewBuilderFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Fedale\GridviewBundle\Column\ActionButton;
use App\Repository\Customer\CustomerLocationRepository;


#[Route('/bundle')]
class BundleController extends AbstractController
{
    public function __construct(
        private GridviewBuilderFactory $gridviewBuilderFactory,
        #[Autowire(service: 'fedale_gridview.gridview_builder')]
        private GridviewBuilderInterface $gridviewBuilder,
        //private CalendarBuilderFactory $calendarBuilderFactory,
        private CustomerSearchModel $customerSearchModel,
        private CustomerLocationRepository $locationRepository,
    ) {
    }

    #[Route('/gridview', name: 'app_gridview', methods: ['GET'])]
    public function grid(
        Request $request
    ): Response {
        //      $pagination->setDefaultPageSize(10);
        $paginationAttributes = [
            'defaultPageSize' => 200
        ];

        $sortAttributes = [
            'id' => [
                'asc' => ['c.id'],
                'desc' => ['c.id'],
                'default' => 'desc',
            ],
            'code' => [
                'asc' => ['c.code'],
                'desc' => ['c.code'],
                'default' => 'desc',
            ],
            'E-Mail' => [
                'asc' => ['c.email'],
                'desc' => ['c.email'],
                'default' => 'desc',
                'label' => 'IDDDD',
            ],
            'Fullname' => [
                'asc' => ['p.firstname', 'p.lastname'],
                'desc' => ['p.firstname', 'p.lastname'],
                'default' => 'asc',
                'label' => 'mylabel',
            ],
            '#' => [
                'asc' => ['c.email'],
                'desc' => ['c.email'],
                'default' => 'desc',
                'label' => 'IDDDD',
            ],
        ];

        $locationChoices = [];
        foreach ($this->locationRepository->findAll() as $loc) {
            // label visibile = "Milano — Sede principale"
            $locationChoices[$loc->getCity() . ' — ' . $loc->getName()] = $loc->getId();
        }

        /**
         *
         */
        $columns = [
            ['type' => 'checkbox'],
            // [//     'type' => 'serial'// ],
            /*
        [
            'attribute' => 'id',
            'filter' => [
                'type' => 'text',
            ]
        ],*/
            'id',
            /*
            'code:raw:code',
            */
            [
                'attribute' => 'code',
                'filter' => [
                    'type' => 'text'
                ]
            ],
            [
                'attribute' => 'profile_fullname',
                'value' => function (array $data, string $key, ColumnInterface $column) {
                    return rand(0, 10) > 5 ?
                        '<strong>' . $data['profile']['fullname'] . '</strong>'
                        :
                        '*****';
                },
                'twigFilter' => 'raw',
                'visible' => true,
                'label' => 'Label 2',
                'filter' => [
                    'type' => 'text',
                ],
                'filterBar' => true,
            ],
            [
                'attribute' => 'email',
                'label' => 'email',
                'value' => function (array $data, string $key, ColumnInterface $column) {
                    return '<strong>' . $data['email'] . '</strong>';
                },
                'twigFilter' => 'raw',
                'filter' => [
                    'type' => 'text',
                ],
                'filterBar' => true,
            ],

            [
                'attribute' => 'locations',
                'label' => 'locations',
                'value' => function (array $data, string $key, ColumnInterface $column) {
                    $arr = [];
                    foreach ($data['locations'] as $location) {
                        $link = sprintf('<a href="/location/%s">%s</a>', $location['id'], $location['name']);
                        array_push($arr, $link);
                    }
                    return $arr;
                },
                'twigFilter' => "join(', ', ' and ')|raw",
                'filter' => [
                    'type' => 'relation',       // ← era 'text'
                    'options' => [
                        'choices' => $locationChoices,
                        'multiple' => true,      // multi-select
                        'searchable' => true,      // search input sopra il select
                    ],
                ],
                'filterBar' => true,
                'visible' => true,                 // mettilo visibile per testare
            ],
            [
                'attribute' => 'active',
                'label' => 'Attivo',
                // La griglia apre già filtrata sui clienti attivi
                'filter' => ['type' => 'boolean', 'default' => '1'],
                'filterBar' => true,
                'value' => fn(array $data) => $data['active'] ? 'Sì' : 'No',
            ],
            [
                'attribute' => 'createdAt',
                'label' => 'Creato il',
                'twigFilter' => "date('d/m/Y')",
                'filter' => ['type' => 'date', 'default' => ['from' => '2023-01-01', 'to' => null]],
            ],
            [
                'attribute' => 't.name',
                'value' => function (array $data, string $key, ColumnInterface $column) {
                    return $data['username'];
                },
                'filter' => ['type' => 'text'],
                'filterBar' => true,
            ],
            [
                'type' => 'action',
                'layout' => '{view} {edit} {clone} {delete}',
                'buttons' => [
                    'clone' => new ActionButton(
                        fn(array $row) => sprintf(
                            '<a href="/customers/%d/clone" title="Clone">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                    </svg>
                </a>',
                            $row['id']
                        ),
                    ),
                ],
            ]

        ];

        /*
        $queryBuilder = $entityManager
            ->getRepository(\App\Entity\Customer\Customer::class)
            ->createQueryBuilder('c')
            ->select('c ', 'p', 'l')
            ->join('c.profile', 'p')
            ->join('c.locations', 'l')
            ;
        */
        $dataProvider = [
            // 'queryBuilder' => $queryBuilder,
            'model' => \App\Entity\Customer\Customer::class,
            'pagination' => $paginationAttributes,
            'sort' => ['map' => $sortAttributes],
        ];

        //$dataProvider->setQueryBuilder($queryBuilder);
        //$dataProvider->setPaginationAttributes($paginationAttributes);
        //   $dataProvider->setSort($sort);


        // Order matters! Try to switch setColumns() / setFilterModel()

        /* * @var Gridview $gridview */
        $gridview = $this->createGridviewBuilder()
            ->setSearchModel($this->customerSearchModel)
            ->setOptions([
                'layout' => [
                    // 'shell' => '{toolbar} {header} {dataview} {footer}',
                    // columnVisibility e filterBar resi nella sidebar di pagina
                    // (vedi gridview/with_sidebar.html.twig). La form resta attorno
                    // alla sola tabella; la filterBar staccata si collega per id.
                    'shell' => '{header} {dataview} {footer}',
                    // La filterBar vive solo nella sidebar: la `header` qui elenca
                    // i widget direttamente (niente {filterBar}), altrimenti
                    // verrebbe resa due volte (header della griglia + sidebar) e
                    // Symfony Form solleva "field already rendered".
                    'header'   => '{globalSearch} {filterSubmit} {addButton} {columnVisibility}',
                ],
            ])
            ->setAttributes([
                'class' => 'table',
                'row' => [
                    'class' => 'row-class'
                ],
                'header' => [
                    'class' => 'row-header'
                ],
                'container' => [
                    'class' => 'row-container',
                    'data-type' => 'my-custom-type'
                ]
            ])
            ->setDataProvider($dataProvider)
            ->setColumns($columns)
            ->renderGridview();


        return $gridview->renderGrid('gridview/with_sidebar.html.twig', []);//, ['pagination' => $pagination]); //, 'form' => $form->createView()]);
    }

    public function createGridviewBuilder(): GridviewBuilderInterface
    {
        return $this->gridviewBuilderFactory->createGridviewBuilder();
    }

}
