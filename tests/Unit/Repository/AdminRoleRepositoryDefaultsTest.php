<?php

declare(strict_types=1);

namespace Mt2Cms\Tests\Unit\Repository;

use Mt2Cms\Admin\AdminSections;
use Mt2Cms\Repository\AdminRoleRepository;
use PHPUnit\Framework\TestCase;

final class AdminRoleRepositoryDefaultsTest extends TestCase
{
    public function testInstallDoesNotShipPlaceholderRoles(): void
    {
        self::assertFalse(method_exists(AdminRoleRepository::class, 'seedDefaults'));
        self::assertFalse(method_exists(AdminSections::class, 'defaultSupportSections'));
        self::assertFalse(method_exists(AdminSections::class, 'defaultContentSections'));

        $createRolesSql = (string) file_get_contents(BASE_DIR . '/src/Setup/migrations/003_dynamic_roles.sql');

        self::assertStringNotContainsString("'support'", $createRolesSql);
        self::assertStringNotContainsString("'content'", $createRolesSql);
    }
}
