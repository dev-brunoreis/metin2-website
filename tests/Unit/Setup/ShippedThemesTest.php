<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Setup;

use Metin2Website\Setup\ThemeCatalog;
use Metin2Website\Theme\ThemeResolver;
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

    public function testHanjiIsPublicChildOfDefaultWithLayoutColumns(): void
    {
        $catalog = new ThemeCatalog(BASE_DIR . '/themes');

        self::assertTrue($catalog->isPublic('hanji'));
        self::assertSame('default', $catalog->meta('hanji')['parent'] ?? null);
        self::assertTrue($catalog->supportsFeature('hanji', 'layout_columns'));

        foreach ([
            'assets/css/tokens.css',
            'assets/css/hanji.css',
            'assets/img/paper-grain.svg',
            'assets/img/brush.svg',
            'layouts/_shell.json',
            'layouts/home.json',
            'templates/layouts/shell.twig',
            'templates/components/navbar.twig',
            'templates/components/home-hero.twig',
            'templates/components/footer.twig',
            'templates/pages/home.twig',
        ] as $file) {
            self::assertFileExists(BASE_DIR . '/themes/hanji/' . $file);
        }

        self::assertStringContainsString(
            'github.com/dev-brunoreis/metin2-website',
            (string) file_get_contents(BASE_DIR . '/themes/hanji/templates/components/footer.twig'),
        );
    }

    public function testHanjiShowsHeroAndGalleryOnlyOnHome(): void
    {
        $resolver = new ThemeResolver(BASE_DIR . '/themes', 'hanji');

        $home = array_column($resolver->resolveLayout('home')['slots']['banner'] ?? [], 'id');
        $news = array_column($resolver->resolveLayout('news')['slots']['banner'] ?? [], 'id');

        self::assertSame(['hanji-hero', 'hanji-gallery'], $home);
        self::assertSame([], $news);
    }
}
