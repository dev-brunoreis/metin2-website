<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Setup;

use Metin2Website\Setup\MigrationRunner;
use PHPUnit\Framework\TestCase;

final class MigrationRunnerTest extends TestCase
{
    public function testLatestVersionIsBaselineSchema(): void
    {
        self::assertSame(1, MigrationRunner::latestVersion());
    }
}
