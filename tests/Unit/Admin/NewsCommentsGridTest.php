<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Admin;

use Metin2Website\Admin\Grid\Definitions\NewsCommentsGrid;
use Metin2Website\Admin\Grid\Definitions\NewsPostCommentsGrid;
use PHPUnit\Framework\TestCase;

final class NewsCommentsGridTest extends TestCase
{
    public function testHubListsAllStatusesWithPostLinkAndWrapBody(): void
    {
        $columns = [];

        foreach (NewsCommentsGrid::definition()->spec()->columns as $column) {
            $columns[(string) $column['key']] = $column;
        }

        self::assertSame('link', $columns['news_title']['type']);
        self::assertSame('/admin/content/news/posts/{news_id}', $columns['news_title']['href']);
        self::assertSame('wrap', $columns['body']['type']);
        self::assertSame('badge', $columns['status']['type']);
        self::assertArrayHasKey('pending', $columns['status']['badgeMap']);
        self::assertArrayHasKey('approved', $columns['status']['badgeMap']);
    }

    public function testPostGridWrapsCommentBody(): void
    {
        $body = null;

        foreach (NewsPostCommentsGrid::definition(7)->spec()->columns as $column) {
            if (($column['key'] ?? '') === 'body') {
                $body = $column;
                break;
            }
        }

        self::assertNotNull($body);
        self::assertSame('wrap', $body['type']);
        self::assertSame('admin-grid-wrap-cell', $body['class']);
    }
}
