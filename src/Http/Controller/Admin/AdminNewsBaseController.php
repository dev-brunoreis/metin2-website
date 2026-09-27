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
use Metin2Website\Service\NotificationService;
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
        protected NotificationService $notifications,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator, $adminAuth, $adminTheme, $auditLog, $acl);
    }

    protected function moderateComment(int $id, string $status): bool
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return false;
        }

        $comment = $this->comments->findById($id);

        if ($comment === null) {
            return false;
        }

        if ((string) $comment['status'] === $status) {
            return false;
        }

        if (!$this->comments->setStatus($id, $status)) {
            return false;
        }

        $accountId = (int) ($comment['account_id'] ?? 0);
        $newsId = (int) ($comment['news_id'] ?? 0);
        $news = $newsId > 0 ? $this->news->findById($newsId) : null;
        $title = $news !== null ? (string) ($news['title'] ?? '') : '';

        if ($status === 'approved') {
            $this->notifications->newsCommentApproved($accountId, $id, $title);
        } else {
            $this->notifications->newsCommentRejected($accountId, $id, $title);
        }

        return true;
    }
}
