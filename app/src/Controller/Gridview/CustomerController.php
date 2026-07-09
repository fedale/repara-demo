<?php

namespace App\Controller\Gridview;

use App\Entity\Customer\Customer;
use App\Entity\Customer\CustomerGroup;
use App\Entity\Customer\CustomerProfile;
use App\Entity\Customer\CustomerRole;
use App\Entity\Customer\CustomerType;
use App\Repository\Customer\CustomerLocationRepository;
use Doctrine\Common\Collections\Collection;
use Fedale\GridviewBundle\Contract\GridCrudHandlerInterface;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Fedale\GridviewBundle\Crud\CrudButton;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/customer', name: 'gridview_customer_')]
class CustomerController extends AbstractCrudGridController
{
    public function __construct(
        private CustomerLocationRepository $locationRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /** Hashes the submitted plain password for new/cloned customers. */
    protected function beforeSave(FormInterface $form, string $mode): void
    {
        if (!\in_array($mode, [GridCrudHandlerInterface::MODE_ADD, GridCrudHandlerInterface::MODE_CLONE], true)) {
            return;
        }

        /** @var Customer $customer */
        $customer = $form->getData();
        // The plainPassword control is required in add/clone, so it is present.
        $plain = (string) $form->get('plainPassword')->getData();
        $customer->setPassword($this->passwordHasher->hashPassword($customer, $plain));
    }

    /**
     * Returns the customer's profile, lazily creating and wiring one when the
     * customer has none yet (cascade persist on Customer::$profile saves it).
     */
    private function profileOf(Customer $customer): CustomerProfile
    {
        $profile = $customer->getProfile();
        if ($profile === null) {
            $profile = new CustomerProfile();
            $customer->setProfile($profile); // setProfile() also sets the owning side
        }

        return $profile;
    }

    protected function getDataClass(): string
    {
        return Customer::class;
    }

    // Grid id default ("customer") derived from the entity short name.
    protected function viewConfig(): array
    {
        return [
            // Responsive collapse: on narrow screens the least important columns
            // (highest priority number) fold into an expandable detail row.
            'options' => [
                'responsive' => true,
                'pagination' => ['mode' => 'infinite'],
            ],
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'model' => Customer::class,
            'pagination' => ['defaultPageSize' => 20],
            // Not shown in the grid; their getters eager-load relations, so
            // keep them out of normalization. 'roles' and 'groups' are
            // fetch-joined in CustomerRepository::search() and displayed.
            'ignoredAttributes' => ['users'],
            'sort' => [
                'map' => [
                    'id' => ['asc' => ['c.id'], 'desc' => ['c.id'], 'default' => 'desc'],
                    'active' => ['asc' => ['c.active'], 'desc' => ['c.active'], 'default' => 'asc'],
                    'username' => ['asc' => ['c.email'], 'desc' => ['c.email'], 'default' => 'asc'],
                ],
            ],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected function buildColumns(): array
    {
        $typeChoices = [];
        foreach ($this->em()->getRepository(CustomerType::class)->findAll() as $type) {
            $typeChoices[$type->getName()] = $type->getId();
        }

        $locationChoices = [];
        foreach ($this->locationRepository->findAll() as $location) {
            $locationChoices[$location->getCity() . ' — ' . $location->getName()] = $location->getId();
        }

        $groupChoices = [];
        foreach ($this->em()->getRepository(CustomerGroup::class)->findAll() as $group) {
            $groupChoices[$group->getName()] = $group->getId();
        }

        return [
            // selection checkbox (enables bulk actions)
            ['type' => 'checkbox'],
            // id (integer)
            'id',
            // code (string)
            [
                'attribute' => 'code',
                'label' => 'col.customer.code',
                'filter' => ['type' => 'text'],
                // 'filterBar' => true,
                'showInDeleteConfirm' => true,
                'editable' => true,
                'control' => [
                    'type' => 'text',
                    'required' => true,
                    'requiredMessage' => 'Il codice è obbligatorio.',
                    'unique' => true,
                    'uniqueMessage' => 'Esiste già un cliente con questo codice.',
                ],
                // Mostrato in modo composito dentro la colonna username: nascosto
                // dalla griglia ma editabile in Create/Update.
                'active' => ['inIndex' => false],
            ],
            // username (string)
            [
                'attribute' => 'username',
                'label' => 'col.customer.username',
                'filter' => ['type' => 'text'],
                'showInDeleteConfirm' => true,
                'editable' => false,
                'control' => [
                    'type' => 'text',
                    'required' => true,
                    'requiredMessage' => 'Lo username è obbligatorio.',
                    'unique' => true,
                    'uniqueMessage' => 'Username già in uso.',
                ],
                'type' => 'html',
                'valueGetter' => fn(array $data) => $data['profile']['fullname'] . ' <br> '
                    . $data['username'] . ' <br> '
                    . $data['email'] . ' <br> '
                    . $data['code']
            ],
            // roles (ManyToMany) — bound via getRoleEntities()/addRole(); getRoles()
            // is reserved by the Security contract (returns string codes).
            [
                'attribute' => 'roles',
                'label' => 'col.customer.roles',
                'type' => 'relation',
                'priority' => 10,
                'value' => fn(array $d) => implode(', ', $d['roles'] ?? []),
                'control' => [
                    'options' => [
                        'class' => CustomerRole::class,
                        'choice_label' => 'name',
                        'multiple' => true,
                        'getter' => fn(Customer $c) => $c->getRoleEntities(),
                        'setter' => function (Customer $c, iterable $roles): void {
                            // EntityType's MergeDoctrineCollectionListener mutates the
                            // original collection in place; snapshot before clear().
                            $new = $roles instanceof Collection ? $roles->toArray() : iterator_to_array($roles);
                            $c->getRoleEntities()->clear();
                            foreach ($new as $role) {
                                $c->addRole($role);
                            }
                        },

                    ],
                ],
            ],
            // profile (OneToOne) — fullname (display only: virtual attribute, no setter)
            [
                'attribute' => 'profile_fullname',
                'label' => 'col.customer.fullname',
                'value' => fn(array $data) => $data['profile']['fullname'] ?? '—',
                'filter' => ['type' => 'text'],
                // 'filterBar' => true,
                // Display-only (nessun control): mostrato dentro username, nascosto dalla griglia.
                'active' => ['inIndex' => false],

            ],
            // profile.firstname — edited through the (cascade-persisted) CustomerProfile
            [
                'attribute' => 'firstname',
                'label' => 'col.customer.firstname',
                'visible' => false,
                'control' => [
                    'type' => 'text',
                    'required' => false,
                    'options' => [
                        'getter' => fn(Customer $c) => $c->getProfile()?->getFirstname(),
                        'setter' => fn(Customer $c, ?string $v) => $this->profileOf($c)->setFirstname($v),
                    ],
                ],
                'active' => ['inIndex' => false],
            ],
            // profile.lastname — required (NOT NULL on customer_profile)
            [
                'attribute' => 'lastname',
                'label' => 'col.customer.lastname',
                'visible' => false,
                'control' => [
                    'type' => 'text',
                    'required' => true,
                    'requiredMessage' => 'Il cognome è obbligatorio.',
                    'options' => [
                        'getter' => fn(Customer $c) => $c->getProfile()?->getLastname(),
                        'setter' => fn(Customer $c, ?string $v) => $this->profileOf($c)->setLastname((string) $v),
                    ],
                ],
                'active' => ['inIndex' => false],
            ],
            // email (string)
            [
                'attribute' => 'email',
                'label' => 'col.customer.email',
                'filter' => ['type' => 'text'],
                // 'filterBar' => true,
                'showInDeleteConfirm' => true,
                'editable' => true,
                'control' => [
                    'type' => 'email',
                    'required' => true,
                    'requiredMessage' => "L'e-mail è obbligatoria.",
                    'unique' => true,
                    'uniqueMessage' => 'Esiste già un cliente con questa e-mail.',
                ],
                // Mostrato in modo composito dentro la colonna username: nascosto
                // dalla griglia ma editabile in Create/Update.
                'active' => ['inIndex' => false],

            ],
            // type (ManyToOne) — relation control binds a managed CustomerType entity
            [
                'attribute' => 'type',
                'label' => 'col.customer.type',
                'type' => 'relation',
                'priority' => 5,
                'value' => fn(array $data) => $data['type']['name'] ?? '—',
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $typeChoices, 'multiple' => true, 'searchable' => true],
                ],
                // 'filterBar' => true,
                'batchUpdate' => true,
                'editable' => true,
                'control' => [
                    'required' => true,
                    'options' => ['class' => CustomerType::class, 'choice_label' => 'name'],
                ],
            ],
            // groups (ManyToMany) — multi relation control
            [
                'attribute' => 'groups',
                'label' => 'col.customer.groups',
                'type' => 'relation',
                'priority' => 20,
                'value' => fn(array $data) => implode(
                    ', ',
                    array_map(fn(array $group) => $group['name'], $data['groups'] ?? [])
                ),
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $groupChoices, 'multiple' => true, 'searchable' => true],
                ],
                'control' => ['options' => ['class' => CustomerGroup::class, 'choice_label' => 'name', 'multiple' => true]],
            ],
            // locations (OneToMany) — display only: inverse side, FK owned by CustomerLocation
            [
                'attribute' => 'locations',
                'label' => 'col.customer.locations',
                'priority' => 30,
                'value' => fn(array $data) => implode(
                    ', ',
                    array_map(fn(array $location) => $location['name'], $data['locations'] ?? [])
                ),
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $locationChoices, 'multiple' => true, 'searchable' => true],
                ],
                // 'filterBar' => true,
            ],
            // active (boolean) — checkbox control (not required, else it must be checked)
            [
                'attribute' => 'active',
                'label' => 'col.customer.active',
                'type' => 'boolean',
                'value' => fn(array $data) => $data['active'] ? 'Sì' : 'No',
                'filter' => ['type' => 'boolean'],
                // 'filterBar' => true,
                'batchUpdate' => true,
                'editable' => true,
                'control' => ['required' => false],
            ],
            // actions — add/edit/clone/delete wired to the CRUD modal
            [
                'type' => 'action',
                'label' => 'col.customer.actions',
                'layout' => '{edit} {clone} {delete}',
                'buttons' => [
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
