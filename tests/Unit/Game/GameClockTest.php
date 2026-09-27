<?php

declare(strict_types=1);

namespace Mt2Cms\Tests\Unit\Game;

use Mt2Cms\Game\Display;
use Mt2Cms\Game\GameClock;
use Mt2Cms\I18n\Translator;
use Mt2Cms\Repository\PlayerRepository;
use Mt2Cms\Repository\UnstuckRepository;
use Mt2Cms\Service\SettingsService;
use Mt2Cms\Service\UnstuckService;
use PHPUnit\Framework\TestCase;

final class GameClockTest extends TestCase
{
    public function testElapsedUsesGameUnixNowNotPhpClock(): void
    {
        $parsed = new \DateTimeImmutable('2026-09-27 02:29:25', new \DateTimeZone('UTC'));
        $clock = GameClock::fixed($parsed->getTimestamp() + 360, 0);

        self::assertSame(360, $clock->elapsedSeconds('2026-09-27 02:29:25'));
    }

    public function testSessionOffsetCorrectsTimezoneSkew(): void
    {
        $parsed = new \DateTimeImmutable('2026-09-27 02:29:25', new \DateTimeZone('UTC'));
        $clock = GameClock::fixed($parsed->getTimestamp() + 3960, -3600);

        self::assertSame(360, $clock->elapsedSeconds('2026-09-27 02:29:25'));
    }

    public function testGameDatetimeShowsMinutesWhenPhpWouldShowAnHour(): void
    {
        $parsed = new \DateTimeImmutable('2026-09-27 02:29:00', new \DateTimeZone('UTC'));
        $clock = GameClock::fixed($parsed->getTimestamp() + 360, 0);
        $display = new Display(new Translator(BASE_DIR . '/lang', 'en', 'en'), $clock);

        $label = $display->datetime('2026-09-27 02:29:00', true);

        self::assertStringContainsString('6 min ago', $label);
        self::assertStringNotContainsString('1h ago', $label);
    }

    public function testRecentlyActiveUsesWindowOnGameClock(): void
    {
        $parsed = new \DateTimeImmutable('2026-09-27 02:29:25', new \DateTimeZone('UTC'));
        $clock = GameClock::fixed($parsed->getTimestamp() + 120, 0);

        self::assertTrue($clock->isRecentlyActive('2026-09-27 02:29:25', 15));
        self::assertFalse($clock->isRecentlyActive('2026-09-27 02:29:25', 1));
        self::assertFalse($clock->isRecentlyActive('', 15));
    }

    public function testMarkOnlineAddsPresenceFromLastPlay(): void
    {
        $parsed = new \DateTimeImmutable('2026-09-27 02:29:25', new \DateTimeZone('UTC'));
        $clock = GameClock::fixed($parsed->getTimestamp() + 120, 0);

        $rows = $clock->markOnline([
            ['id' => 1, 'last_play' => '2026-09-27 02:29:25'],
            ['id' => 2, 'last_play' => '2026-09-27 01:00:00'],
        ], 15);

        self::assertTrue($rows[0]['online']);
        self::assertFalse($rows[1]['online']);
    }

    public function testUnstuckTreatsRecentGameClockAsOnline(): void
    {
        $players = $this->createMock(PlayerRepository::class);
        $settings = $this->createMock(SettingsService::class);
        $players->method('findById')->willReturn([
            'id' => 10,
            'account_id' => 1,
            'last_play' => '2026-09-27 02:29:25',
        ]);
        $settings->method('onlineWindowMinutes')->willReturn(15);

        $parsed = new \DateTimeImmutable('2026-09-27 02:29:25', new \DateTimeZone('UTC'));
        $service = new UnstuckService(
            $this->createMock(UnstuckRepository::class),
            $players,
            $settings,
            GameClock::fixed($parsed->getTimestamp() + 360, 0),
        );

        self::assertFalse($service->isOffline(10));
    }
}
