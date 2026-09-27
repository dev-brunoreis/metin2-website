<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Game;

use Metin2Website\Game\Display;
use Metin2Website\I18n\Translator;
use PHPUnit\Framework\TestCase;

final class DisplayMapTest extends TestCase
{
    public function testKnownMapUsesLocaleName(): void
    {
        $display = new Display(new Translator(BASE_DIR . '/lang', 'en', 'en'));

        self::assertSame('Pyungmoo', $display->map(41));
        self::assertSame('Yongan', $display->map(1));
        self::assertSame('Joan', $display->map(21));
    }

    public function testUnknownMapFallsBackToId(): void
    {
        $display = new Display(new Translator(BASE_DIR . '/lang', 'en', 'en'));

        self::assertSame('Map 99', $display->map(99));
        self::assertSame('Map 0', $display->map(0));
    }
}
