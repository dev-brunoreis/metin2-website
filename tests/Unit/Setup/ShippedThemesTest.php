<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Setup;

use Metin2Website\Setup\ThemeCatalog;
use PHPUnit\Framework\TestCase;

final class ShippedThemesTest extends TestCase
{
    public function testStarterIsPublicChildOfDefaultWithLayoutColumns(): void
    {
        $catalog = new ThemeCatalog(BASE_DIR . '/themes');

        self::assertTrue($catalog->isPublic('starter'));
        self::assertTrue($catalog->isPublic('default'));
        self::assertFalse($catalog->isPublic('admin'));
        self::assertSame('default', $catalog->meta('starter')['parent'] ?? null);
        self::assertTrue($catalog->supportsFeature('starter', 'layout_columns'));
    }
}
