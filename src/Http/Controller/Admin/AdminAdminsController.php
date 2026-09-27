<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller\Admin;

use Metin2Website\Admin\Grid\Definitions\AdminsGrid;
use Metin2Website\Admin\AdminPermissions;
use Metin2Website\Admin\AdminResourceCatalog;
use Metin2Website\Admin\Grid\GridRunner;
use Metin2Website\Auth\AdminAuth;
use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Translator;
use Metin2Website\Repository\AdminRepository;
use Metin2Website\Repository\AdminRoleRepository;
use Metin2Website\Repository\AdminTotpRepository;
use Metin2Website\Service\AclService;
use Metin2Website\Service\AdminAuditService;
use Metin2Website\Theme\ThemeEngine;

class AdminAdminsController extends AdminController
{
    public function __construct(
        ThemeEngine $theme,
        Auth $auth,
        Csrf $csrf,
        Translator $translator,
        AdminAuth $adminAuth,
        ThemeEngine $adminTheme,
        AclService $acl,
        AdminAuditService $auditLog,
        private AdminRepository $admins,
        private AdminRoleRepository $roles,
        private AdminTotpRepository $totp,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator, $adminAuth, $adminTheme, $auditLog, $acl);
    }

    public function index(): Response
    {
        $spec = AdminsGrid::definition()
            ->filterOptions('role', $this->admins->roleFilterOptions(), false)
            ->spec();
        $query = $this->gridQuery($spec);
        $grid = GridRunner::fetch(
            $spec,
            $query,
            fn ($q) => $this->admins->countForGrid($q),
            fn ($q) => $this->admins->listForGrid($q),
        );

        return $this->adminView('admins', 'pages/admins.twig', [
            'title' => $this->t('admin.admins.title'),
            'pageLead' => $this->t('admin.admins.lead'),
            'headerHref' => '/admin/system/admins/new',
            'headerActionLabel' => $this->t('admin.admins.create'),
            'grid' => $grid,
        ]);
    }

    public function mass(): Response
    {
        $selfId = (int) ($this->adminAuth->id() ?? 0);

        return $this->runMassActions(
            AdminsGrid::definition()->spec(),
            '/admin/system/admins',
            [
                'delete' => function (int $id) use ($selfId): bool {
                    if ($id === $selfId) {
                        return false;
                    }

                    return $this->admins->delete($id);
                },
            ],
            'admin',
            'admin.admins.mass_done',
            'system/admins/mass',
        );
    }

    public function create(): Response
    {
        return $this->formView();
    }

    public function store(): Response
    {
        if ($redirect = $this->requireAdminSection('admins')) {
            return $redirect;
        }

        if (!$this->assertCsrf()) {
            $this->flash('error', $this->t('auth.invalid_csrf'));

            return $this->redirect('/admin/system/admins/new');
        }

        $input = $this->formInput();

        try {
            $id = $this->admins->create(
                $input['login'],
                (string) ($_POST['password'] ?? ''),
                $input['role'],
            );

            if ($input['use_custom_acl']) {
                $this->admins->update($id, $input['login'], $input['role'], true);
                $this->acl->saveAdminResources($id, $input['resources']);
            }

            $this->audit('admin.create', 'admin', $id, [
                'role' => $input['role'],
                'use_custom_acl' => $input['use_custom_acl'],
                'resources' => $input['use_custom_acl'] ? $input['resources'] : null,
            ]);
            $this->flash('success', $this->t('admin.admins.created'));

            return $this->redirect('/admin/system/admins');
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            return $this->formView($input, $this->t($e->getMessage()), 422);
        }
    }

    public function edit(string $id): Response
    {
        $admin = $this->admins->findById((int) $id);

        if ($admin === null) {
            return $this->adminView('admins', 'pages/placeholder.twig', [
                'title' => $this->t('admin.admins.not_found'),
            ], 404);
        }

        return $this->formView([
            'admin' => $admin,
            'resources' => $this->acl->adminResources((int) $admin['id']),
        ]);
    }

    public function update(string $id): Response
    {
        if ($redirect = $this->requireAdminSection('admins')) {
            return $redirect;
        }

        if (!$this->assertCsrf()) {
            $this->flash('error', $this->t('auth.invalid_csrf'));

            return $this->redirect('/admin/system/admins/' . $id);
        }

        $input = $this->formInput();
        $adminId = (int) $id;
        $password = trim((string) ($_POST['password'] ?? ''));

        try {
            $existing = $this->admins->findById($adminId);
            $before = [
                'login' => $existing['login'] ?? '',
                'role' => $existing['role'] ?? '',
                'use_custom_acl' => (bool) ($existing['use_custom_acl'] ?? false),
                'resources' => $this->acl->adminResources($adminId),
            ];

            $this->admins->update(
                $adminId,
                $input['login'],
                $input['role'],
                $input['use_custom_acl'],
                $password !== '' ? $password : null,
            );

            if ($input['use_custom_acl']) {
                $this->acl->saveAdminResources($adminId, $input['resources']);
            } else {
                $this->acl->saveAdminResources($adminId, []);
            }

            $after = [
                'login' => $input['login'],
                'role' => $input['role'],
                'use_custom_acl' => $input['use_custom_acl'],
                'resources' => $input['use_custom_acl'] ? $input['resources'] : [],
            ];

            if ($password !== '') {
                $before['password_changed'] = false;
                $after['password_changed'] = true;
            }

            $this->auditChange('admin.update', 'admin', $adminId, $before, $after);
            $this->flash('success', $this->t('admin.admins.updated'));

            return $this->redirect('/admin/system/admins');
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            $admin = $this->admins->findById($adminId);

            return $this->formView([
                'admin' => $admin ?? ['id' => $adminId],
                'resources' => $input['resources'],
            ] + $input, $this->t($e->getMessage()), 422);
        }
    }

    public function destroy(string $id): Response
    {
        if ($redirect = $this->requireAdminSection('admins')) {
            return $redirect;
        }

        if (!$this->assertCsrf()) {
            $this->flash('error', $this->t('auth.invalid_csrf'));

            return $this->redirect('/admin/system/admins');
        }

        $adminId = (int) $id;

        if ($adminId === (int) ($this->adminAuth->id() ?? 0)) {
            $this->flash('error', $this->t('admin.admins.cannot_delete_self'));

            return $this->redirect('/admin/system/admins');
        }

        try {
            if ($this->admins->delete($adminId)) {
                $this->audit('admin.delete', 'admin', $adminId);
                $this->flash('success', $this->t('admin.admins.deleted'));
            }
        } catch (\RuntimeException $e) {
            $this->flash('error', $this->t($e->getMessage()));
        }

        return $this->redirect('/admin/system/admins');
    }

    public function resetTwoFactor(string $id): Response
    {
        if ($redirect = $this->requireAdminSection('admins')) {
            return $redirect;
        }

        if (!$this->assertCsrf()) {
            $this->flash('error', $this->t('auth.invalid_csrf'));

            return $this->redirect('/admin/system/admins/' . $id);
        }

        $adminId = (int) $id;
        $admin = $this->admins->findById($adminId);

        if ($admin === null) {
            $this->flash('error', $this->t('admin.admins.not_found'));

            return $this->redirect('/admin/system/admins');
        }

        $this->totp->disable($adminId);
        $this->audit('admin.2fa.reset', 'admin', $adminId);
        $this->flash('success', $this->t('admin.2fa.reset_done'));

        return $this->redirect('/admin/system/admins/' . $id);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function formView(array $data = [], ?string $error = null, int $status = 200): Response
    {
        $admin = $data['admin'] ?? null;
        $isEdit = is_array($admin) && isset($admin['id']);
        $roleOptions = $this->roles->listForAssign();
        $defaultRole = $roleOptions[0]['slug'] ?? AdminPermissions::ROLE_SUPER;

        return $this->adminView('admins', 'pages/admin-form.twig', [
            'title' => $this->t($isEdit ? 'admin.admins.edit_title' : 'admin.admins.create_title'),
            'pageLead' => $this->t($isEdit ? 'admin.admins.edit_lead' : 'admin.admins.create_lead'),
            'formId' => 'admin-admin-form',
            'isEdit' => $isEdit,
            'admin' => $admin ?? [
                'login' => $data['login'] ?? '',
                'role' => $data['role'] ?? $defaultRole,
                'use_custom_acl' => $data['use_custom_acl'] ?? false,
            ],
            'selectedResources' => $data['resources'] ?? [],
            'resourceTree' => AdminResourceCatalog::tree(),
            'roleOptions' => $roleOptions,
            'error' => $error,
        ], $status);
    }

    /**
     * @return array{login: string, role: string, use_custom_acl: bool, resources: list<string>}
     */
    private function formInput(): array
    {
        $role = strtolower(trim((string) ($_POST['role'] ?? '')));
        $useCustomAcl = (string) ($_POST['use_custom_acl'] ?? '') === '1';
        $resources = [];

        foreach ((array) ($_POST['resources'] ?? []) as $resourceId) {
            $resourceId = (string) $resourceId;

            if ($resourceId !== '') {
                $resources[] = $resourceId;
            }
        }

        if (AdminPermissions::isSuper($role)) {
            $useCustomAcl = false;
            $resources = [];
        }

        return [
            'login' => trim((string) ($_POST['login'] ?? '')),
            'role' => $role,
            'use_custom_acl' => $useCustomAcl,
            'resources' => $resources,
        ];
    }
}
