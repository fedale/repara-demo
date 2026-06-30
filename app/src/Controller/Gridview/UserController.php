<?php

namespace App\Controller\Gridview;

use App\Entity\User\User;
use App\Entity\User\UserGroup;
use App\Entity\User\UserRole;
use App\Entity\User\UserType;
use Doctrine\Common\Collections\Collection;
use Fedale\GridviewBundle\Contract\GridCrudHandlerInterface;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Fedale\GridviewBundle\Crud\CrudButton;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/user', name: 'gridview_user_')]
class UserController extends AbstractCrudGridController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    protected function getDataClass(): string
    {
        return User::class;
    }

    protected function viewConfig(): array
    {
        return [
            'id' => 'user',
            'exportFilename' => 'utenti',
            'title' => 'Utente',
            'addLabel' => 'Nuovo utente',
            'formView' => 'gridview/user/_form.html.twig',
            'options' => [
                'reorderColumns' => true,
                'globalSearch' => ['u.username', 'u.email'],
            ],
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'models' => User::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'id' => ['asc' => ['u.id'], 'desc' => ['u.id'], 'default' => 'desc'],
                'username' => ['asc' => ['u.username'], 'desc' => ['u.username'], 'default' => 'asc'],
                'email' => ['asc' => ['u.email'], 'desc' => ['u.email'], 'default' => 'asc'],
            ],
        ];
    }

    /** Hashes the submitted plain password for new/cloned users. */
    protected function beforeSave(FormInterface $form, string $mode): void
    {
        if (!\in_array($mode, [GridCrudHandlerInterface::MODE_ADD, GridCrudHandlerInterface::MODE_CLONE], true)) {
            return;
        }

        /** @var User $user */
        $user = $form->getData();
        // The plainPassword control is required in add/clone, so it is present.
        $plain = (string) $form->get('plainPassword')->getData();
        $user->setPassword($this->passwordHasher->hashPassword($user, $plain));
    }

    /**
     * @return array<int, mixed>
     */
    protected function buildColumns(): array
    {
        $em = $this->em();

        $typeChoices = [];
        foreach ($em->getRepository(UserType::class)->findAll() as $type) {
            $typeChoices[$type->getName()] = $type->getId();
        }

        $groupChoices = [];
        foreach ($em->getRepository(UserGroup::class)->findAll() as $group) {
            $groupChoices[$group->getName()] = $group->getId();
        }

        $roleChoices = [];
        foreach ($em->getRepository(UserRole::class)->findAll() as $role) {
            $roleChoices[$role->getName()] = $role->getId();
        }

        return [
            // selection checkbox (enables bulk actions)
            ['type' => 'checkbox'],
            // id (integer)
            'id',
            // code (string)
            [
                'attribute' => 'code',
                'label' => 'Codice',
                'filter' => ['type' => 'text'],
                // 'filterBar' => true,
                'showInDeleteConfirm' => true,
                'editable' => true,
                'control' => [
                    'type' => 'text',
                    'required' => true,
                    'requiredMessage' => 'Il codice è obbligatorio.',
                    'unique' => true,
                    'uniqueMessage' => 'Esiste già un utente con questo codice.',
                ],
            ],
            // username (string)
            [
                'attribute' => 'username',
                'label' => 'Username',
                'filter' => ['type' => 'text'],
                // 'filterBar' => true,
                'showInDeleteConfirm' => true,
                'editable' => true,
                'control' => [
                    'type' => 'text',
                    'required' => true,
                    'requiredMessage' => 'Lo username è obbligatorio.',
                    'unique' => true,
                    'uniqueMessage' => 'Username già in uso.',
                ],
            ],
            // plainPassword — write-only control, required only when creating.
            [
                'attribute' => 'plainPassword',
                'label' => 'Password',
                'visible' => false,
                'sortable' => false,
                'filterable' => false,
                'control' => [
                    'type' => 'text',
                    'modes' => ['add', 'clone'],
                    'required' => true,
                    'requiredMessage' => 'La password è obbligatoria.',
                ],
            ],
            // profile (OneToOne) — fullname (display only, no control)
            [
                'attribute' => 'profile_fullname',
                'label' => 'Nominativo',
                'value' => fn(array $data) => $data['profile']['fullname'] ?? '—',
                'filter' => ['type' => 'text'],
                // 'filterBar' => true,
            ],
            // email (string)
            [
                'attribute' => 'email',
                'label' => 'E-mail',
                'filter' => ['type' => 'text'],
                // 'filterBar' => true,
                'showInDeleteConfirm' => true,
                'editable' => true,
                'control' => [
                    'type' => 'text',
                    'required' => true,
                    'requiredMessage' => "L'e-mail è obbligatoria.",
                    'unique' => true,
                    'uniqueMessage' => 'Esiste già un utente con questa e-mail.',
                ],
            ],
            // type (ManyToOne) — relation control binds a managed UserType entity
            [
                'attribute' => 'type',
                'label' => 'Tipo',
                'type' => 'relation',
                'value' => fn(array $data) => $data['type']['name'] ?? '—',
                'filter' => ['options' => ['choices' => $typeChoices, 'multiple' => true, 'searchable' => true]],
                // 'filterBar' => true,
                'batchUpdate' => true,
                'editable' => true,
                'control' => ['options' => ['class' => UserType::class, 'choice_label' => 'name']],
            ],
            // groups (ManyToMany) — multi relation control
            [
                'attribute' => 'groups',
                'label' => 'Gruppi',
                'type' => 'relation',
                'value' => fn(array $data) => implode(
                    ', ',
                    array_map(fn(array $group) => $group['name'], $data['groups'] ?? [])
                ),
                'filter' => ['options' => ['choices' => $groupChoices, 'multiple' => true, 'searchable' => true]],
                'control' => ['options' => ['class' => UserGroup::class, 'choice_label' => 'name', 'multiple' => true]],
            ],
            // roles — editable via getter/setter: getRoles() follows the Security
            // contract (string[]), so the form binds to the UserRole collection
            // through getRoleEntities()/addRole() instead of PropertyAccess.
            [
                'attribute' => 'roles',
                'label' => 'Ruoli',
                'type' => 'relation',
                'filter' => ['options' => ['choices' => $roleChoices, 'multiple' => true, 'searchable' => true]],
                'value' => fn(array $data) => implode(', ', $data['roles'] ?? []),
                'control' => [
                    'options' => [
                        'class' => UserRole::class,
                        'choice_label' => 'name',
                        'multiple' => true,
                        'getter' => fn(User $u) => $u->getRoleEntities(),
                        'setter' => function (User $u, iterable $roles): void {
                            // $roles may be the very same Collection instance returned by
                            // getRoleEntities() (EntityType's MergeDoctrineCollectionListener
                            // mutates the original collection in place). Snapshot it before
                            // clear(), otherwise clear() would empty the list we iterate.
                            $new = $roles instanceof Collection ? $roles->toArray() : iterator_to_array($roles);
                            $u->getRoleEntities()->clear();
                            foreach ($new as $role) {
                                $u->addRole($role);
                            }
                        },
                    ]
                ],
            ],
            // active (boolean) — checkbox control (not required, else it must be checked)
            [
                'attribute' => 'active',
                'label' => 'Attivo',
                'type' => 'boolean',
                'filter' => true,
                // 'filterBar' => true,
                'batchUpdate' => true,
                'editable' => true,
                'control' => ['required' => false],
            ],
            // lastLoginAt (unix timestamp)
            [
                'attribute' => 'lastLoginAt',
                'label' => 'Ultimo accesso',
                'value' => fn(array $data) => !empty($data['lastLoginAt'])
                    ? date('d/m/Y H:i', $data['lastLoginAt'])
                    : '—',
            ],
            // createdAt (datetime)
            [
                'attribute' => 'createdAt',
                'label' => 'Creato il',
                'type' => 'date',
                'twigFilter' => "date('d/m/Y')",
                'filter' => true,
            ],
            // actions — add/edit/clone/delete wired to the CRUD modal
            [
                'type' => 'action',
                'label' => 'Azioni',
                'layout' => '{edit} {clone} {delete}',
                'buttons' => [
                    'edit' => fn(array $row) => CrudButton::edit(
                        $this->generateUrl($this->routeName('update'), ['id' => $row['id']]),
                        $this->config('mode')
                    ),
                    'clone' => fn(array $row) => CrudButton::clone(
                        $this->generateUrl($this->routeName('clone'), ['id' => $row['id']]),
                        $this->config('mode')
                    ),
                    'delete' => fn(array $row) => CrudButton::delete(
                        $this->generateUrl($this->routeName('delete'), ['id' => $row['id']])
                    ),
                ],
            ],
        ];
    }
}
