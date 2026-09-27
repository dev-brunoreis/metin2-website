<?php

declare(strict_types=1);

namespace Mt2Cms\Tests\Unit\Admin\Grid;

use Mt2Cms\Admin\Grid\Definitions\PlayersGrid;
use PHPUnit\Framework\TestCase;

final class PlayersGridTest extends TestCase
{
    public function testNameColumnShowsLastPlayPresence(): void
    {
        $name = null;

        foreach (PlayersGrid::definition()->spec()->columns as $column) {
            if (($column['key'] ?? '') === 'name') {
                $name = $column;
                break;
            }
        }

        self::assertIsArray($name);
        self::assertTrue($name['presence'] ?? false);
        self::assertSame('face', $name['icon'] ?? null);
    }
}
