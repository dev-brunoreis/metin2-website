<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller\Admin;

use Metin2Website\Admin\AdminPaths;
use Metin2Website\Admin\Grid\Definitions\BannersGrid;
use Metin2Website\Admin\Grid\GridRunner;
use Metin2Website\Auth\AdminAuth;
use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Translator;
use Metin2Website\Repository\BannerRepository;
use Metin2Website\Service\AclService;
use Metin2Website\Service\AdminAuditService;
use Metin2Website\Service\BannerUploadService;
use Metin2Website\Theme\ThemeEngine;

class AdminBannersHubController extends AdminController
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
        private BannerRepository $banners,
        private BannerUploadService $uploads,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator, $adminAuth, $adminTheme, $auditLog, $acl);
    }

    public function index(): Response
    {
        if ((string) ($_GET['tab'] ?? '') === 'settings') {
            if ($redirect = $this->requireAdmin()) {
                return $redirect;
            }

            return $this->redirect(AdminPaths::settingsBanners());
        }

        $spec = BannersGrid::definition()->spec();
        $grid = GridRunner::fetch(
            $spec,
            $this->gridQuery($spec),
            fn ($q) => $this->banners->countForGrid($q),
            fn ($q) => $this->banners->listForGrid($q),
        );

        return $this->adminView('banners', 'pages/banners.twig', [
            'title' => $this->t('admin.banners.title'),
            'pageLead' => $this->t('admin.banners.lead'),
            'headerHref' => AdminPaths::contentBannerNew(),
            'headerActionLabel' => $this->t('admin.banners.create'),
            'grid' => $grid,
        ]);
    }

    public function create(): Response
    {
        if ($redirect = $this->requireAdminResource('content/banners/slides/create')) {
            return $redirect;
        }

        return $this->formView(null);
    }

    public function edit(string $id): Response
    {
        if ($redirect = $this->requireAdminResource('content/banners/slides/edit')) {
            return $redirect;
        }

        $row = $this->banners->findById((int) $id);

        if ($row === null) {
            $this->flash('error', $this->t('admin.banners.not_found'));

            return $this->redirect(AdminPaths::contentBanners());
        }

        return $this->formView($row);
    }

    public function store(): Response
    {
        return $this->save(null);
    }

    public function update(string $id): Response
    {
        return $this->save((int) $id);
    }

    public function mass(): Response
    {
        return $this->runMassActions(
            BannersGrid::definition()->spec(),
            AdminPaths::contentBanners(),
            [
                'enable' => function (int $id): bool {
                    return $this->banners->findById($id) !== null && $this->banners->setEnabled($id, true);
                },
                'disable' => function (int $id): bool {
                    return $this->banners->findById($id) !== null && $this->banners->setEnabled($id, false);
                },
                'delete' => function (int $id): bool {
                    $row = $this->banners->findById($id);

                    if ($row === null) {
                        return false;
                    }

                    $this->uploads->deleteByOriginalPath((string) ($row['original_path'] ?? ''));

                    return $this->banners->delete($id);
                },
            ],
            'banner',
            'admin.banners.mass_done',
            'content/banners/slides/mass',
        );
    }

    private function save(?int $id): Response
    {
        $resource = $id === null ? 'content/banners/slides/create' : 'content/banners/slides/edit';

        if ($redirect = $this->requireAdminResource($resource)) {
            return $redirect;
        }

        if (!$this->assertCsrf()) {
            $this->flash('error', $this->t('auth.invalid_csrf'));

            return $this->redirect($id === null ? AdminPaths::contentBannerNew() : AdminPaths::contentBanner((int) $id));
        }

        $data = [];

        try {
            $data = $this->readForm();
            $hasUpload = isset($_FILES['image']) && is_array($_FILES['image'])
                && (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

            if ($id === null && !$hasUpload) {
                throw new \InvalidArgumentException('admin.banners.image_required');
            }

            if ($hasUpload) {
                $stored = $this->uploads->store($_FILES['image']);
                $data['original_path'] = $stored['original_path'];
                $data['variants'] = $stored['variants'];
            }

            if ($id === null) {
                $newId = $this->banners->create($data);
                $this->audit('banner.create', 'banner', $newId);
            } else {
                $existing = $this->banners->findById($id);

                if ($existing === null) {
                    $this->flash('error', $this->t('admin.banners.not_found'));

                    return $this->redirect(AdminPaths::contentBanners());
                }

                if (!isset($data['original_path'])) {
                    $data['original_path'] = $existing['original_path'];
                    $data['variants'] = $existing['variants'];
                } else {
                    $this->uploads->deleteByOriginalPath((string) ($existing['original_path'] ?? ''));
                }

                $this->banners->update($id, $data);
                $this->audit('banner.update', 'banner', $id);
            }

            $this->flash('success', $this->t('admin.saved'));

            return $this->redirect(AdminPaths::contentBanners());
        } catch (\InvalidArgumentException | \RuntimeException | \JsonException $e) {
            $message = $this->t($e->getMessage());

            return $this->formView(
                $id !== null ? array_merge($this->banners->findById($id) ?? [], $data) : $data,
                $message,
                422,
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readForm(): array
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $alt = trim((string) ($_POST['alt'] ?? ''));
        $link = trim((string) ($_POST['link_url'] ?? ''));

        if ($title === '') {
            throw new \InvalidArgumentException('admin.banners.title_required');
        }

        if ($alt === '') {
            $alt = $title;
        }

        if ($link !== '' && !preg_match('#^https?://#i', $link) && !str_starts_with($link, '/')) {
            throw new \InvalidArgumentException('admin.banners.invalid_link');
        }

        return [
            'title' => mb_substr($title, 0, 120),
            'alt' => mb_substr($alt, 0, 180),
            'link_url' => $link !== '' ? mb_substr($link, 0, 500) : null,
            'sort_order' => max(0, (int) ($_POST['sort_order'] ?? 0)),
            'enabled' => isset($_POST['enabled']),
        ];
    }

    /**
     * @param array<string, mixed>|null $values
     */
    private function formView(?array $values, ?string $error = null, int $status = 200): Response
    {
        $values ??= [
            'title' => '',
            'alt' => '',
            'link_url' => '',
            'sort_order' => 0,
            'enabled' => true,
        ];

        return $this->adminView('banners', 'pages/banner-form.twig', [
            'title' => isset($values['id']) ? $this->t('admin.banners.edit') : $this->t('admin.banners.create'),
            'pageLead' => $this->t('admin.banners.form_lead'),
            'formId' => 'admin-banner-form',
            'saveLabel' => $this->t('admin.save'),
            'banner' => $values,
            'error' => $error,
        ], $status);
    }
}
