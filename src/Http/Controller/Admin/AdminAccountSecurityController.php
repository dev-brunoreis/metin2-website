<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller\Admin;

use Metin2Website\Auth\AdminAuth;
use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\Auth\Totp;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Translator;
use Metin2Website\Repository\AdminTotpRepository;
use Metin2Website\Service\AclService;
use Metin2Website\Service\AdminAuditService;
use Metin2Website\Service\SettingsService;
use Metin2Website\Theme\ThemeEngine;

class AdminAccountSecurityController extends AdminController
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
        private AdminTotpRepository $totp,
        private SettingsService $settings,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator, $adminAuth, $adminTheme, $auditLog, $acl);
    }

    public function show(): Response
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $user = $this->adminAuth->user();
        $enabled = $user !== null && (int) ($user['totp_enabled'] ?? 0) === 1;
        $secret = $this->adminAuth->peekEnrollSecret();
        $provisioningUri = null;
        $qrDataUri = null;

        if (!$enabled && $secret !== null) {
            $provisioningUri = Totp::provisioningUri(
                $secret,
                (string) ($user['login'] ?? 'admin'),
                'Metin2 website Admin',
            );
            $qrDataUri = Totp::qrDataUri($provisioningUri);
        }

        $recoveryCodes = $_SESSION['_admin_2fa_recovery_codes'] ?? null;
        unset($_SESSION['_admin_2fa_recovery_codes']);

        return $this->renderAdmin('panel', [
            'activeSection' => 'account-security',
            'activeGroup' => 'settings',
            'contentTemplate' => 'pages/account-security.twig',
            'adminUser' => $user,
            'title' => $this->t('admin.2fa.account_title'),
            'pageLead' => $this->t('admin.2fa.account_lead'),
            'totpEnabled' => $enabled,
            'enrollSecret' => $secret,
            'provisioningUri' => $provisioningUri,
            'qrDataUri' => $qrDataUri,
            'twoFactorRequired' => $this->settings->adminTwoFactorRequired(),
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    public function startEnroll(): Response
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->assertCsrf()) {
            $this->flash('error', $this->t('auth.invalid_csrf'));

            return $this->redirect('/admin/account/security');
        }

        $user = $this->adminAuth->user();

        if ($user !== null && (int) ($user['totp_enabled'] ?? 0) === 1) {
            return $this->redirect('/admin/account/security');
        }

        $this->adminAuth->setEnrollSecret(Totp::generateSecret());
        unset($_SESSION['_admin_2fa_recovery_codes']);

        return $this->redirect('/admin/account/security');
    }

    public function confirmEnroll(): Response
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        if (!$this->assertCsrf()) {
            $this->flash('error', $this->t('auth.invalid_csrf'));

            return $this->redirect('/admin/account/security');
        }

        $adminId = (int) ($this->adminAuth->id() ?? 0);
        $secret = $this->adminAuth->pullEnrollSecret();
        $code = trim((string) ($_POST['totp_code'] ?? ''));

        if ($adminId < 1 || $secret === null) {
            $this->flash('error', $this->t('admin.2fa.enroll_expired'));

            return $this->redirect('/admin/account/security');
        }

        if (!Totp::verify($secret, $code)) {
            $this->adminAuth->setEnrollSecret($secret);
            $this->flash('error', $this->t('admin.2fa.invalid_code'));

            return $this->redirect('/admin/account/security');
        }

        try {
            $recoveryCodes = $this->totp->enable($adminId, $secret);
        } catch (\Throwable $e) {
            $this->adminAuth->setEnrollSecret($secret);

            throw $e;
        }

        $_SESSION['_admin_2fa_recovery_codes'] = $recoveryCodes;
        $this->audit('admin.2fa.enable', 'admin', $adminId);
        $this->flash('success', $this->t('admin.2fa.enabled'));

        return $this->redirect('/admin/account/security');
    }
}
