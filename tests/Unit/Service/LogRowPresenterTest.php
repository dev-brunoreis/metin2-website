<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Service;

use Metin2Website\I18n\Translator;
use Metin2Website\Service\LogRowPresenter;
use PHPUnit\Framework\TestCase;

final class LogRowPresenterTest extends TestCase
{
    public function testGoldlogShopSellSummary(): void
    {
        $presenter = new LogRowPresenter(new Translator(dirname(__DIR__, 3) . '/lang', 'en'));
        $rows = $presenter->decorate('goldlog', [[
            'date' => '2026-09-12',
            'time' => '20:00:00',
            'pid' => 42,
            'what' => 150000,
            'how' => 'SHOP_SELL',
            'hint' => 'Long Sword 1',
        ]], [42 => 'Hero']);

        self::assertStringContainsString('Hero', $rows[0]['_summary']);
        self::assertStringContainsString('Long Sword', $rows[0]['_summary']);
        self::assertStringContainsString('150,000', $rows[0]['_summary']);
        self::assertStringContainsString('player shop', $rows[0]['_summary']);
        self::assertSame('Sold in player shop', $rows[0]['_how_label']);
        self::assertSame(150000, $rows[0]['_yang']);
    }

    public function testItemLogShopSellSummary(): void
    {
        $presenter = new LogRowPresenter(new Translator(dirname(__DIR__, 3) . '/lang', 'en'));
        $rows = $presenter->decorate('log', [[
            'type' => 'ITEM',
            'time' => '2026-09-12 22:49:27',
            'who' => 2,
            'what' => 80000072,
            'how' => 'SHOP_SELL',
            'hint' => 'Lunar Sword+9 1([SA]Admin) 40 1',
            'vnum' => 229,
        ]], [2 => 'Test'], [229 => 'Lunar Sword+9']);

        self::assertStringContainsString('Test', $rows[0]['_summary']);
        self::assertStringContainsString('Lunar Sword+9', $rows[0]['_summary']);
        self::assertStringContainsString('40', $rows[0]['_summary']);
        self::assertStringContainsString('[SA]Admin', $rows[0]['_summary']);
        self::assertSame(40, $rows[0]['_yang']);
    }

    public function testNeededLookupsCollectsPlayerAndVnum(): void
    {
        $presenter = new LogRowPresenter(new Translator(dirname(__DIR__, 3) . '/lang', 'en'));
        $need = $presenter->neededLookups('cube', [[
            'pid' => 9,
            'item_vnum' => 299,
            'item_count' => 1,
            'success' => 1,
        ]]);

        self::assertSame([9], $need['players']);
        self::assertSame([299], $need['vnums']);
    }

    public function testSocketLogDoesNotResolveWhoAsPlayer(): void
    {
        $presenter = new LogRowPresenter(new Translator(dirname(__DIR__, 3) . '/lang', 'en'));
        $rows = $presenter->decorate('log', [[
            'type' => 'ITEM',
            'time' => '2026-09-27 01:34:54',
            'who' => 2,
            'x' => 1,
            'y' => 0,
            'what' => 40000016,
            'how' => 'SET_SOCKET',
            'hint' => '',
            'vnum' => 2100,
        ]], [2 => 'vendedora'], [2100 => "Bull's Horn Bow"]);

        self::assertSame('socket', $rows[0]['_who_kind']);
        self::assertSame('', $rows[0]['who_name']);
        self::assertStringNotContainsString('vendedora', $rows[0]['_summary']);
        self::assertSame("Socket 2 = 1 on Bull's Horn Bow (uid 40000016)", $rows[0]['_summary']);
    }

    public function testSocketLogSkipsPlayerLookup(): void
    {
        $presenter = new LogRowPresenter(new Translator(dirname(__DIR__, 3) . '/lang', 'en'));
        $need = $presenter->neededLookups('log', [[
            'who' => 2,
            'how' => 'SET_SOCKET',
            'vnum' => 2100,
        ]]);

        self::assertSame([], $need['players']);
        self::assertSame([2100], $need['vnums']);
    }

    public function testGetLogStillResolvesPlayer(): void
    {
        $presenter = new LogRowPresenter(new Translator(dirname(__DIR__, 3) . '/lang', 'en'));
        $rows = $presenter->decorate('log', [[
            'type' => 'ITEM',
            'who' => 1,
            'x' => 83510,
            'y' => 571255,
            'what' => 40000016,
            'how' => 'GET',
            'hint' => "Bull's Horn Bow+0 1 2100",
            'vnum' => 2100,
        ]], [1 => 'adminn'], [2100 => "Bull's Horn Bow"]);

        self::assertArrayNotHasKey('_who_kind', $rows[0]);
        self::assertSame('adminn', $rows[0]['who_name']);
        self::assertStringContainsString('adminn', $rows[0]['_summary']);
    }

    public function testAttrLogUsesBonusSlotNotPlayer(): void
    {
        $presenter = new LogRowPresenter(new Translator(dirname(__DIR__, 3) . '/lang', 'en'));
        $rows = $presenter->decorate('log', [[
            'type' => 'ITEM',
            'who' => 0,
            'x' => 17,
            'y' => 12,
            'what' => 40000016,
            'how' => 'SET_ATTR',
            'vnum' => 2100,
        ]], [0 => 'nobody'], [2100 => "Bull's Horn Bow"]);

        self::assertSame('attr', $rows[0]['_who_kind']);
        self::assertSame('', $rows[0]['who_name']);
        self::assertSame("Bonus slot 0: type 17, value 12 on Bull's Horn Bow (uid 40000016)", $rows[0]['_summary']);
    }
}
