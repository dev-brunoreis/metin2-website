<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Admin;

use Metin2Website\Admin\AdminAuditTarget;
use PHPUnit\Framework\TestCase;

final class AdminAuditTargetTest extends TestCase
{
    public function testHrefMapsKnownTypesToAdminPages(): void
    {
        self::assertSame('/admin/game/accounts/12', AdminAuditTarget::href('account', 12));
        self::assertSame('/admin/game/characters/9', AdminAuditTarget::href('player', 9));
        self::assertSame('/admin/system/admins/3', AdminAuditTarget::href('admin', 3));
        self::assertSame('/admin/game/guilds/4', AdminAuditTarget::href('guild', 4));
        self::assertSame('/admin/content/news/posts/8', AdminAuditTarget::href('news', 8));
        self::assertSame('/admin/content/tickets/5', AdminAuditTarget::href('ticket', 5));
        self::assertSame('/admin/store?tab=categories&id=7', AdminAuditTarget::href('item_shop_category', 7));
        self::assertSame('/admin/game/economy/27001', AdminAuditTarget::href('economy_item', 27001));
        self::assertSame('/admin/settings?tab=news', AdminAuditTarget::href('news_settings', null));
    }

    public function testHrefFallsBackToSectionWhenIdIsMissing(): void
    {
        self::assertSame('/admin/game/accounts', AdminAuditTarget::href('account', null));
        self::assertSame('/admin/game/bans', AdminAuditTarget::href('ban', 15));
        self::assertNull(AdminAuditTarget::href('unknown', 1));
    }

    public function testHrefUsesSnapshotForRolesAndGuildComments(): void
    {
        self::assertSame(
            '/admin/system/roles/editor',
            AdminAuditTarget::href('admin_role', null, ['slug' => 'editor']),
        );
        self::assertSame(
            '/admin/game/guilds/22',
            AdminAuditTarget::href('guild_comment', 90, ['guild_id' => 22]),
        );
        self::assertSame('/admin/system/roles', AdminAuditTarget::href('admin_role', null, ['slug' => '../x']));
    }

    public function testAdminHrefRequiresPositiveId(): void
    {
        self::assertSame('/admin/system/admins/4', AdminAuditTarget::adminHref(4));
        self::assertNull(AdminAuditTarget::adminHref(0));
        self::assertNull(AdminAuditTarget::adminHref(null));
    }

    public function testLabelFromSnapshotPrefersAfterThenBefore(): void
    {
        self::assertSame('newlogin', AdminAuditTarget::labelFromSnapshot('account', [
            'before' => ['login' => 'oldlogin'],
            'after' => ['login' => 'newlogin'],
        ]));
        self::assertSame('Hero', AdminAuditTarget::labelFromSnapshot('player', [
            'name' => 'Hero',
        ]));
        self::assertSame('Refund', AdminAuditTarget::labelFromSnapshot('ticket', [
            'after' => ['subject' => 'Refund'],
        ]));
        self::assertNull(AdminAuditTarget::labelFromSnapshot('account', ['cash' => 10]));
    }
}
