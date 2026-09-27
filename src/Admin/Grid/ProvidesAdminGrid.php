<?php

declare(strict_types=1);

namespace Metin2Website\Admin\Grid;

interface ProvidesAdminGrid
{
    public function countForGrid(GridQuery $query): int;

    /**
     * @return list<array<string, mixed>>
     */
    public function listForGrid(GridQuery $query): array;
}
