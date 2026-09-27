<?php

declare(strict_types=1);

namespace Metin2Website\Repository;

use Metin2Website\Admin\Grid\Definitions\AdminAuditGrid;
use Metin2Website\Admin\AdminAuditMeta;
use Metin2Website\Admin\Grid\GridQuery;
use Metin2Website\Admin\Grid\GridSql;
use Metin2Website\Admin\Grid\ProvidesAdminGrid;
use Metin2Website\Support\Database;

class AdminAuditRepository extends Repository implements ProvidesAdminGrid
{
    /** @var array<string, array{table: string, column: string}> */
    private const CMS_LABELS = [
        'admin' => ['table' => 'admins', 'column' => 'login'],
        'news' => ['table' => 'news', 'column' => 'title'],
        'event' => ['table' => 'cms_events', 'column' => 'title'],
        'ticket' => ['table' => 'tickets', 'column' => 'subject'],
        'banner' => ['table' => 'cms_banners', 'column' => 'title'],
        'download' => ['table' => 'cms_downloads', 'column' => 'title'],
        'package' => ['table' => 'cms_cash_packages', 'column' => 'title'],
        'item_shop_category' => ['table' => 'item_shop_categories', 'column' => 'name'],
        'ban' => ['table' => 'cms_bans', 'column' => 'account_login'],
    ];

    protected function database(): string
    {
        return 'cms';
    }


    /**
     * @param array<string, mixed>|null $meta
     */
    public function insert(
        int $adminId,
        string $login,
        string $action,
        string $targetType,
        ?int $targetId,
        ?array $meta,
        string $ip,
    ): void {
        $metaJson = null;

        if ($meta !== null && $meta !== []) {
            $metaJson = json_encode($meta, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        $this->db()->execute(
            'INSERT INTO admin_audit_log
                (admin_id, login, action, target_type, target_id, meta, ip, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
            [$adminId, $login, $action, $targetType, $targetId, $metaJson, $ip],
        );
    }

    public function countForGrid(GridQuery $query): int
    {
        [$where, $params] = $this->gridWhere($query);

        return (int) $this->db()->fetchColumn(
            'SELECT COUNT(*) FROM admin_audit_log' . $where,
            $params,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForGrid(GridQuery $query): array
    {
        [$where, $params] = $this->gridWhere($query);
        $params[] = $query->perPage;
        $params[] = $query->offset();
        $order = GridSql::orderBy($query, AdminAuditGrid::definition()->sortMap(), 'id DESC');

        $rows = $this->db()->fetchAll(
            'SELECT id, admin_id, login, action, target_type, target_id, meta, ip, created_at
             FROM admin_audit_log' . $where . $order . '
             LIMIT ? OFFSET ?',
            $params,
        );

        foreach ($rows as &$row) {
            $columns = AdminAuditMeta::displayColumns($row['meta'] ?? null);
            $row['target_snapshot'] = AdminAuditMeta::decoded($row['meta'] ?? null);
            $row['before'] = $columns['before'];
            $row['after'] = $columns['after'];
            unset($row['meta']);
        }

        unset($row);

        return $rows;
    }

    /**
     * @param array<string, list<int>> $idsByType
     * @return array<string, array<int, string>>
     */
    public function cmsLabelsByType(array $idsByType): array
    {
        $out = [];

        foreach (self::CMS_LABELS as $type => $spec) {
            $ids = $idsByType[$type] ?? [];

            if ($ids === []) {
                continue;
            }

            $map = $this->labelsFromTable($spec['table'], $spec['column'], $ids);

            if ($map !== []) {
                $out[$type] = $map;
            }
        }

        return $out;
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function gridWhere(GridQuery $query): array
    {
        return GridSql::where($query, AdminAuditGrid::definition()->filterSql());
    }

    /**
     * @param list<int> $ids
     * @return array<int, string>
     */
    private function labelsFromTable(string $table, string $column, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $ids),
            static fn (int $id): bool => $id > 0,
        )));
        $ids = array_slice($ids, 0, 200);

        if ($ids === [] || !$this->schemaTableExists($table)) {
            return [];
        }

        $table = Database::quoteIdentifier($table);
        $column = Database::quoteIdentifier($column);
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $rows = $this->db()->fetchAll(
            'SELECT id, `' . $column . '` AS label FROM `' . $table . '` WHERE id IN (' . $placeholders . ')',
            $ids,
        );
        $map = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $label = trim((string) ($row['label'] ?? ''));

            if ($id > 0 && $label !== '') {
                $map[$id] = $label;
            }
        }

        return $map;
    }
}
