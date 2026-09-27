<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Game;

use Metin2Website\Game\Display;
use Metin2Website\I18n\Translator;
use PHPUnit\Framework\TestCase;

final class DisplayProtoTokenTest extends TestCase
{
    public function testUsesFormTokenLabels(): void
    {
        $display = new Display(new Translator(BASE_DIR . '/lang', 'en', 'en'));

        self::assertSame('Monster', $display->protoToken('MONSTER'));
        self::assertSame('Strong knight', $display->protoToken('S_KNIGHT'));
        self::assertSame('Weapon', $display->protoToken('ITEM_WEAPON'));
    }

    public function testFallsBackToRawToken(): void
    {
        $display = new Display(new Translator(BASE_DIR . '/lang', 'en', 'en'));

        self::assertSame('NOT_A_REAL_TOKEN', $display->protoToken('NOT_A_REAL_TOKEN'));
        self::assertSame('—', $display->protoToken(''));
    }
}
