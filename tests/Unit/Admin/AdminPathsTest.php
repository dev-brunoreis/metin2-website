<?php

declare(strict_types=1);

namespace Mt2Cms\Tests\Unit\Admin;

use Mt2Cms\Admin\AdminPaths;
use Mt2Cms\Admin\AdminSections;
use Mt2Cms\Admin\LogCatalog;
use Mt2Cms\Game\GameProfile;
use PHPUnit\Framework\TestCase;

final class AdminPathsTest extends TestCase
{
    public function testSectionPaths(): void
    {
        self::assertSame('/admin/logs?tab=connections', AdminPaths::logs());
        self::assertSame('/admin/logs?tab=hack_log', AdminPaths::logs('hack_log'));
        self::assertSame('/admin/content/news?tab=comments', AdminPaths::contentNews('comments'));
        self::assertSame('/admin/store?tab=categories&new=1', AdminPaths::storeCategoryNew());
        self::assertSame('/admin/store?tab=categories&new=1&parent_id=4', AdminPaths::storeCategoryNew(4));
        self::assertSame('/admin/store?tab=categories&id=12&panel=products', AdminPaths::storeCategoryEdit(12, 'products'));
        self::assertSame('/admin/system/roles', AdminPaths::systemRoles());
        self::assertSame('/admin/population', AdminPaths::population());
        self::assertSame('/admin/population', AdminSections::sectionPath('population'));
    }

    public function testAdminSectionsUsesCanonicalPaths(): void
    {
        self::assertSame('/admin/logs?tab=connections', AdminSections::sectionPath('logs'));
        self::assertSame('/admin/store', AdminSections::sectionPath('store'));
        self::assertSame('/admin/game-data/shops', AdminSections::sectionPath('shops'));
        self::assertNull(AdminSections::sectionPath('drops'));
        self::assertNotContains('drops', AdminSections::navSectionIds());
        self::assertSame('/admin/settings', AdminSections::sectionPath('settings'));
        self::assertSame('/admin/settings?tab=registration', AdminPaths::settingsRegistration());
        self::assertSame('/admin/settings?tab=banners', AdminPaths::settingsBanners());
    }

    public function testWithoutUnavailableProtoHidesItemsAndMobs(): void
    {
        $dir = sys_get_temp_dir() . '/mt2cms-admin-proto-nav-' . bin2hex(random_bytes(4));
        mkdir($dir . '/schema', 0777, true);
        copy(BASE_DIR . '/game/config.json', $dir . '/config.json');
        copy(BASE_DIR . '/game/schema/item.json', $dir . '/schema/item.json');
        copy(BASE_DIR . '/game/schema/mob.json', $dir . '/schema/mob.json');

        $sections = AdminSections::withoutUnavailableProto(AdminSections::all(), GameProfile::load($dir));
        $ids = [];

        foreach ($sections as $group) {
            foreach ($group['children'] as $child) {
                $ids[] = $child['id'];
            }
        }

        self::assertNotContains('items', $ids);
        self::assertNotContains('mobs', $ids);
        self::assertNotContains('drops', $ids);
        self::assertContains('shops', $ids);
        self::assertContains('gms', $ids);
    }

    public function testWithoutUnavailableProtoKeepsItemsWhenDumpsExist(): void
    {
        $dir = sys_get_temp_dir() . '/mt2cms-admin-proto-nav-' . bin2hex(random_bytes(4));
        mkdir($dir . '/schema', 0777, true);
        mkdir($dir . '/db', 0777, true);
        copy(BASE_DIR . '/game/config.json', $dir . '/config.json');
        copy(BASE_DIR . '/game/schema/item.json', $dir . '/schema/item.json');
        copy(BASE_DIR . '/game/schema/mob.json', $dir . '/schema/mob.json');
        file_put_contents($dir . '/db/item_proto.txt', "VNUM\n");
        file_put_contents($dir . '/db/item_names_en.txt', "VNUM\n");

        $ids = [];

        foreach (AdminSections::withoutUnavailableProto(AdminSections::all(), GameProfile::load($dir)) as $group) {
            foreach ($group['children'] as $child) {
                $ids[] = $child['id'];
            }
        }

        self::assertContains('items', $ids);
        self::assertNotContains('mobs', $ids);
    }

    public function testSettingsGroupIsPinnedToSidebarFooter(): void
    {
        $settings = null;

        foreach (AdminSections::all() as $group) {
            if ($group['id'] === 'settings') {
                $settings = $group;
                break;
            }
        }

        self::assertNotNull($settings);
        self::assertTrue($settings['pinned'] ?? false);
        self::assertSame('settings', AdminSections::pinnedNavItem(AdminSections::all())['id'] ?? null);
    }

    public function testBreadcrumbsLinkParentsAndKeepCurrentPlain(): void
    {
        $list = AdminSections::breadcrumbs('accounts', 'Accounts', '/admin/game/accounts');
        self::assertSame('admin.title', $list[0]['label']);
        self::assertSame('/admin', $list[0]['href']);
        self::assertSame('admin.nav.game', $list[1]['label']);
        self::assertSame('/admin/game/accounts', $list[1]['href']);
        self::assertSame('admin.nav.accounts', $list[2]['label']);
        self::assertNull($list[2]['href']);
        self::assertNull(AdminSections::backHref('accounts', '/admin/game/accounts'));

        $edit = AdminSections::breadcrumbs('accounts', 'Edit account', '/admin/game/accounts/4');
        self::assertSame('/admin/game/accounts', $edit[2]['href']);
        self::assertSame('Edit account', $edit[3]['label']);
        self::assertFalse($edit[3]['translate']);
        self::assertNull($edit[3]['href']);
        self::assertSame('/admin/game/accounts', AdminSections::backHref('accounts', '/admin/game/accounts/4'));
    }

    public function testBreadcrumbsSkipSingleChildGroups(): void
    {
        $crumbs = AdminSections::breadcrumbs('settings', 'Settings', '/admin/settings?tab=locale');
        self::assertCount(2, $crumbs);
        self::assertSame('admin.nav.configuration', $crumbs[1]['label']);
        self::assertNull($crumbs[1]['href']);
        self::assertNull(AdminSections::backHref('settings', '/admin/settings?tab=locale'));
    }

    public function testLogCatalogHasGroupedTabsIncludingConnections(): void
    {
        $groups = LogCatalog::groupedTabs();
        $ids = [];

        foreach ($groups as $group) {
            foreach ($group['tabs'] as $tab) {
                $ids[] = $tab['id'];
            }
        }

        self::assertContains(LogCatalog::CONNECTIONS_ID, $ids);
        self::assertContains('hack_log', $ids);
        self::assertGreaterThan(20, count($ids));
    }
}
