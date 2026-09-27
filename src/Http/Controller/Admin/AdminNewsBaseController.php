<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller\Admin;

use Metin2Website\Auth\AdminAuth;
use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\I18n\Translator;
use Metin2Website\Repository\NewsCommentRepository;
use Metin2Website\Repository\NewsRepository;
use Metin2Website\Service\AclService;
use Metin2Website\Service\AdminAuditService;
use Metin2Website\Service\NewsUploadService;
use Metin2Website\Service\SettingsService;
use Metin2Website\Support\HtmlSanitizer;
use Metin2Website\Theme\ThemeEngine;

abstract class AdminNewsBaseController extends AdminController
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
        protected NewsRepository $news,
        protected NewsCommentRepository $comments,
        protected SettingsService $settings,
        protected HtmlSanitizer $sanitizer,
        protected NewsUploadService $uploads,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator, $adminAuth, $adminTheme, $auditLog, $acl);
    }
}
