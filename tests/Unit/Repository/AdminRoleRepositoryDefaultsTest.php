<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Repository;

use Metin2Website\Admin\AdminSections;
use Metin2Website\Repository\AdminRoleRepository;
use PHPUnit\Framework\TestCase;

final class AdminRoleRepositoryDefaultsTest extends TestCase
{
    public function testInstallDoesNotShipPlaceholderRoles(): void
    {
        self::assertFalse(method_exists(AdminRoleRepository::class, 'seedDefaults'));
        self::assertFalse(method_exists(AdminSections::class, 'defaultSupportSections'));
        self::assertFalse(method_exists(AdminSections::class, 'defaultContentSections'));

        $schemaSql = (string) file_get_contents(BASE_DIR . '/src/Setup/migrations/001_schema.sql');

        self::assertStringNotContainsString("'support'", $schemaSql);
        self::assertStringNotContainsString("'content'", $schemaSql);
    }
}
