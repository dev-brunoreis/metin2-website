<?php

declare(strict_types=1);

namespace Mt2Cms\Tests\Unit\Service;

use Mt2Cms\Repository\AccountRepository;
use Mt2Cms\Repository\AdminAuditRepository;
use Mt2Cms\Repository\GuildRepository;
use Mt2Cms\Repository\PlayerRepository;
use Mt2Cms\Service\AdminAuditTargetService;
use PHPUnit\Framework\TestCase;

final class AdminAuditTargetServiceTest extends TestCase
{
    public function testEnrichResolvesLiveNamesAndAdminLink(): void
    {
        $accounts = $this->createMock(AccountRepository::class);
        $accounts->expects(self::once())
            ->method('loginsByIds')
            ->with([41])
            ->willReturn([41 => 'playerone']);

        $players = $this->createMock(PlayerRepository::class);
        $players->expects(self::once())
            ->method('namesByIds')
            ->with([88])
            ->willReturn([88 => 'Warrior']);

        $guilds = $this->createMock(GuildRepository::class);
        $guilds->expects(self::once())
            ->method('namesByIds')
            ->with([])
            ->willReturn([]);

        $audit = $this->createMock(AdminAuditRepository::class);
        $audit->expects(self::once())
            ->method('cmsLabelsByType')
            ->willReturn([]);

        $service = new AdminAuditTargetService($accounts, $players, $guilds, $audit);
        $rows = $service->enrich([
            [
                'admin_id' => 2,
                'login' => 'root',
                'target_type' => 'account',
                'target_id' => 41,
                'target_snapshot' => [],
            ],
            [
                'admin_id' => 2,
                'login' => 'root',
                'target_type' => 'player',
                'target_id' => '88',
                'target_snapshot' => ['name' => 'stale'],
            ],
        ]);

        self::assertSame('playerone', $rows[0]['target_label']);
        self::assertSame('/admin/game/accounts/41', $rows[0]['target_href']);
        self::assertSame('/admin/system/admins/2', $rows[0]['admin_href']);
        self::assertSame('Warrior', $rows[1]['target_label']);
        self::assertArrayNotHasKey('target_snapshot', $rows[0]);
    }

    public function testEnrichFallsBackToSnapshotWhenLookupMisses(): void
    {
        $accounts = $this->createMock(AccountRepository::class);
        $accounts->method('loginsByIds')->willReturn([]);
        $players = $this->createMock(PlayerRepository::class);
        $players->method('namesByIds')->willReturn([]);
        $guilds = $this->createMock(GuildRepository::class);
        $guilds->method('namesByIds')->willReturn([]);
        $audit = $this->createMock(AdminAuditRepository::class);
        $audit->method('cmsLabelsByType')->willReturn([
            'news' => [5 => 'Welcome'],
        ]);

        $service = new AdminAuditTargetService($accounts, $players, $guilds, $audit);
        $rows = $service->enrich([
            [
                'admin_id' => 1,
                'target_type' => 'account',
                'target_id' => 9,
                'target_snapshot' => ['login' => 'archived'],
            ],
            [
                'admin_id' => 1,
                'target_type' => 'news',
                'target_id' => 5,
                'target_snapshot' => [],
            ],
            [
                'admin_id' => 0,
                'target_type' => 'settings',
                'target_id' => null,
                'target_snapshot' => [],
            ],
        ]);

        self::assertSame('archived', $rows[0]['target_label']);
        self::assertSame('Welcome', $rows[1]['target_label']);
        self::assertSame('/admin/content/news/posts/5', $rows[1]['target_href']);
        self::assertSame('', $rows[2]['target_label']);
        self::assertSame('/admin/settings', $rows[2]['target_href']);
        self::assertNull($rows[2]['admin_href']);
    }
}
