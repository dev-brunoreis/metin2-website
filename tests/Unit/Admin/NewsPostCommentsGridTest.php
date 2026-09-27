<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Admin;

use Metin2Website\Admin\Grid\Definitions\NewsPostCommentsGrid;
use PHPUnit\Framework\TestCase;

final class NewsPostCommentsGridTest extends TestCase
{
    public function testDefinitionScopesToPostAndMassEndpoint(): void
    {
        $def = NewsPostCommentsGrid::definition(42);
        $spec = $def->spec();

        self::assertSame('/admin/content/news/posts/42?tab=comments', $spec->action);
        self::assertSame('admin.news.post_comments_grid', $spec->i18nPrefix);
        self::assertSame('/admin/content/news/posts/42/comments/mass', $spec->massActionPath);
        self::assertTrue($spec->hasMassActions());

        $body = null;

        foreach ($spec->columns as $column) {
            if (($column['key'] ?? '') === 'body') {
                $body = $column;
                break;
            }
        }

        self::assertNotNull($body);
        self::assertSame('wrap', $body['type']);
    }
}
