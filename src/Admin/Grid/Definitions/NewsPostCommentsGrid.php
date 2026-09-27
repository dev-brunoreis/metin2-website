<?php

declare(strict_types=1);

namespace Metin2Website\Admin\Grid\Definitions;

use Metin2Website\Admin\Grid\GridDefinition;

final class NewsPostCommentsGrid
{
    public static function definition(int $newsId): GridDefinition
    {
        $base = '/admin/content/news/posts/' . $newsId . '?tab=comments';

        return GridDefinition::create($base, 'admin.news.post_comments_grid')
            ->defaultSort('created_at')
            ->orderBy([
                'id' => 'c.id',
                'author_login' => 'c.account_login',
                'body' => 'c.body',
                'status' => 'c.status',
                'created_at' => 'c.created_at',
            ])
            ->columns([
                ['key' => 'id', 'label' => 'admin.news.comment_id', 'sort' => 'id', 'type' => 'muted'],
                ['key' => 'author_login', 'label' => 'admin.news.comment_author', 'sort' => 'author_login', 'type' => 'text'],
                ['key' => 'body', 'label' => 'admin.news.comment_body', 'type' => 'wrap', 'class' => 'admin-grid-wrap-cell'],
                ['key' => 'status', 'label' => 'admin.news.comment_status', 'sort' => 'status', 'type' => 'badge', 'badgeMap' => [
                    'pending' => ['class' => 'admin-badge-warn', 'label' => 'admin.news.comment_status_pending'],
                    'approved' => ['class' => 'admin-badge-ok', 'label' => 'admin.news.comment_status_approved'],
                    'rejected' => ['class' => 'admin-badge-muted', 'label' => 'admin.news.comment_status_rejected'],
                ]],
                ['key' => 'created_at', 'label' => 'admin.news.comment_date', 'sort' => 'created_at', 'type' => 'date'],
            ])
            ->filters([
                ['key' => 'status', 'label' => 'admin.news.comment_status', 'type' => 'select', 'options' => [
                    'pending' => 'admin.news.comment_status_pending',
                    'approved' => 'admin.news.comment_status_approved',
                    'rejected' => 'admin.news.comment_status_rejected',
                ]],
            ])
            ->massActions('/admin/content/news/posts/' . $newsId . '/comments/mass', [
                ['id' => 'approve', 'label' => 'admin.grid.approve'],
                ['id' => 'reject', 'label' => 'admin.grid.reject'],
                ['id' => 'delete', 'label' => 'admin.grid.delete', 'confirm' => 'admin.news.confirm_mass_delete_comments'],
            ]);
    }
}
