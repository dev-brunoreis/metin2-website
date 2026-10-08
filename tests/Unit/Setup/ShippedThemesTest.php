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

    public function testDragonGateIsPublicChildOfDefaultWithLayoutColumns(): void
    {
        $catalog = new ThemeCatalog(BASE_DIR . '/themes');

        self::assertTrue($catalog->isPublic('dragon-gate'));
        self::assertSame('default', $catalog->meta('dragon-gate')['parent'] ?? null);
        self::assertFalse($catalog->supportsFeature('dragon-gate', 'layout_columns'));
        self::assertFileExists(BASE_DIR . '/themes/dragon-gate/assets/css/tokens.css');
        self::assertFileExists(BASE_DIR . '/themes/dragon-gate/assets/css/atmosphere.css');
        self::assertFileExists(BASE_DIR . '/themes/dragon-gate/layouts/home.json');
        self::assertFileExists(BASE_DIR . '/themes/dragon-gate/templates/components/home-hero.twig');
        self::assertFileExists(BASE_DIR . '/themes/dragon-gate/templates/components/navbar.twig');
        self::assertFileExists(BASE_DIR . '/themes/dragon-gate/templates/pages/home.twig');
        self::assertFileExists(BASE_DIR . '/themes/dragon-gate/templates/components/account-sidebar.twig');
    }
}
