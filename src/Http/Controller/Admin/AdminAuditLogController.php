<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller\Admin;

use Metin2Website\Admin\Grid\Definitions\AdminAuditGrid;
use Metin2Website\Admin\Grid\GridRunner;
use Metin2Website\Auth\AdminAuth;
use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Translator;
use Metin2Website\Repository\AdminAuditRepository;
use Metin2Website\Service\AclService;
use Metin2Website\Service\AdminAuditService;
use Metin2Website\Service\AdminAuditTargetService;
use Metin2Website\Theme\ThemeEngine;

class AdminAuditLogController extends AdminController
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
        private AdminAuditRepository $auditEntries,
        private AdminAuditTargetService $targets,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator, $adminAuth, $adminTheme, $auditLog, $acl);
    }

    public function index(): Response
    {
        $spec = AdminAuditGrid::definition()->spec();
        $query = $this->gridQuery($spec);
        $grid = GridRunner::fetch(
            $spec,
            $query,
            fn ($q) => $this->auditEntries->countForGrid($q),
            fn ($q) => $this->targets->enrich($this->auditEntries->listForGrid($q)),
        );

        return $this->adminView('audit-log', 'pages/audit-log.twig', [
            'title' => $this->t('admin.audit_log.title'),
            'pageLead' => $this->t('admin.audit_log.lead'),
            'grid' => $grid,
        ]);
    }
}
