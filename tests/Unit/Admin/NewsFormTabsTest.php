<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Admin;

use Metin2Website\Admin\NewsFormTabs;
use PHPUnit\Framework\TestCase;

final class NewsFormTabsTest extends TestCase
{
    public function testDefaultsUnknownTabToData(): void
    {
        self::assertSame('data', NewsFormTabs::normalize('nope', true, true));
        self::assertSame('data', NewsFormTabs::normalize('', false, false));
    }

    public function testAllowsDataAndSeoAlways(): void
    {
        self::assertSame('data', NewsFormTabs::normalize('data', false, false));
        self::assertSame('seo', NewsFormTabs::normalize('seo', false, false));
    }

    public function testCommentsOnlyWhenEditingAndAllowed(): void
    {
        self::assertSame('comments', NewsFormTabs::normalize('comments', true, true));
        self::assertSame('data', NewsFormTabs::normalize('comments', false, true));
        self::assertSame('data', NewsFormTabs::normalize('comments', true, false));
    }
}
