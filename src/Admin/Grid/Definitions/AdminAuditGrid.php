<?php

declare(strict_types=1);

namespace Metin2Website\Admin\Grid\Definitions;

use Metin2Website\Admin\Grid\GridDefinition;

final class AdminAuditGrid
{
    public static function definition(): GridDefinition
    {

        return GridDefinition::create('/admin/system/audit-log', 'admin.audit_log')
            ->defaultSort('created_at', 'desc')
            ->orderBy([
                'id' => 'id',
                'login' => 'login',
                'action' => 'action',
                'target_type' => 'target_type',
                'target_id' => 'target_id',
                'ip' => 'ip',
                'created_at' => 'created_at',
            ])
            ->columns([
                ['key' => 'id', 'label' => 'admin.audit_log.id', 'sort' => 'id', 'type' => 'muted'],
                ['key' => 'created_at', 'label' => 'admin.audit_log.created', 'sort' => 'created_at', 'type' => 'date'],
                ['key' => 'login', 'label' => 'admin.audit_log.login', 'sort' => 'login', 'type' => 'link', 'href' => '/admin/system/admins/{admin_id}'],
                ['key' => 'action', 'label' => 'admin.audit_log.action', 'sort' => 'action', 'type' => 'text'],
                ['key' => 'target_type', 'label' => 'admin.audit_log.target_type', 'sort' => 'target_type', 'type' => 'template', 'template' => 'components/audit-type.twig'],
                ['key' => 'target_id', 'label' => 'admin.audit_log.target', 'sort' => 'target_id', 'type' => 'template', 'template' => 'components/audit-target.twig'],
                ['key' => 'ip', 'label' => 'admin.audit_log.ip', 'sort' => 'ip', 'type' => 'text'],
                ['key' => 'before', 'label' => 'admin.audit_log.before', 'type' => 'template', 'template' => 'components/audit-diff.twig', 'class' => 'admin-audit-diff-cell', 'filter' => false],
                ['key' => 'after', 'label' => 'admin.audit_log.after', 'type' => 'template', 'template' => 'components/audit-diff.twig', 'class' => 'admin-audit-diff-cell', 'filter' => false],
            ])
            ->filters([
                ['key' => 'login', 'label' => 'admin.audit_log.login', 'type' => 'text'],
                ['key' => 'action', 'label' => 'admin.audit_log.action', 'type' => 'text'],
                ['key' => 'target_type', 'label' => 'admin.audit_log.target_type', 'type' => 'text'],
                ['key' => 'target_id', 'label' => 'admin.audit_log.target_id', 'type' => 'text'],
            ]);
        }
}
