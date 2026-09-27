<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller\Admin;

use Metin2Website\Admin\AdminPaths;
use Metin2Website\Auth\AdminAuth;
use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Translator;
use Metin2Website\Service\AclService;
use Metin2Website\Service\AdminAuditService;
use Metin2Website\Service\SettingsService;
use Metin2Website\Theme\ThemeEngine;
use Metin2Website\Service\UnstuckService;

class AdminUnstuckController extends AdminController
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
        private SettingsService $settings,
        private UnstuckService $unstuck,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator, $adminAuth, $adminTheme, $auditLog, $acl);
    }

    public function settings(): Response
    {
        return $this->redirect(AdminPaths::settingsUnstuck());
    }

    public function saveSettings(): Response
    {
        if ($redirect = $this->requireAdminResource('settings/unstuck/edit')) {
            return $redirect;
        }

        if (!$this->assertCsrf()) {
            $this->flash('error', $this->t('auth.invalid_csrf'));

            return $this->redirect(AdminPaths::settingsUnstuck());
        }

        $before = [
            'unstuck_enabled' => $this->settings->unstuckEnabled(),
            'unstuck_cooldown_minutes' => $this->settings->unstuckCooldownMinutes(),
            'unstuck_spawns' => $this->settings->unstuckSpawns(),
        ];

        $this->settings->setUnstuckEnabled(isset($_POST['unstuck_enabled']));
        $this->settings->setUnstuckCooldownMinutes((int) ($_POST['unstuck_cooldown_minutes'] ?? 60));

        $spawns = [];

        foreach ([1, 2, 3] as $empire) {
            $spawns[$empire] = [
                'map_index' => (int) ($_POST['spawn_map_' . $empire] ?? 0),
                'x' => (int) ($_POST['spawn_x_' . $empire] ?? 0),
                'y' => (int) ($_POST['spawn_y_' . $empire] ?? 0),
            ];
        }

        $this->settings->setUnstuckSpawns($spawns);

        $after = [
            'unstuck_enabled' => $this->settings->unstuckEnabled(),
            'unstuck_cooldown_minutes' => $this->settings->unstuckCooldownMinutes(),
            'unstuck_spawns' => $this->settings->unstuckSpawns(),
        ];

        $this->auditChange('settings.unstuck_save', 'settings', null, $before, $after);
        $this->flash('success', $this->t('admin.saved'));

        return $this->redirect(AdminPaths::settingsUnstuck());
    }
}
