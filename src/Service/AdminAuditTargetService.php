<?php

declare(strict_types=1);

namespace Mt2Cms\Service;

use Mt2Cms\Admin\AdminAuditTarget;
use Mt2Cms\Repository\AccountRepository;
use Mt2Cms\Repository\AdminAuditRepository;
use Mt2Cms\Repository\GuildRepository;
use Mt2Cms\Repository\PlayerRepository;

class AdminAuditTargetService
{
    public function __construct(
        private AccountRepository $accounts,
        private PlayerRepository $players,
        private GuildRepository $guilds,
        private AdminAuditRepository $audit,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    public function enrich(array $rows): array
    {
        $idsByType = [];

        foreach ($rows as $row) {
            $type = (string) ($row['target_type'] ?? '');
            $id = self::positiveId($row['target_id'] ?? null);

            if ($type !== '' && $id !== null) {
                $idsByType[$type][] = $id;
            }
        }

        $labels = [
            'account' => $this->accounts->loginsByIds($idsByType['account'] ?? []),
            'player' => $this->players->namesByIds($idsByType['player'] ?? []),
            'guild' => $this->guilds->namesByIds($idsByType['guild'] ?? []),
        ];

        foreach ($this->audit->cmsLabelsByType($idsByType) as $type => $map) {
            $labels[$type] = $map;
        }

        foreach ($rows as &$row) {
            $type = (string) ($row['target_type'] ?? '');
            $id = self::positiveId($row['target_id'] ?? null);
            $snapshot = is_array($row['target_snapshot'] ?? null) ? $row['target_snapshot'] : [];
            $resolved = '';

            if ($id !== null && ($labels[$type][$id] ?? '') !== '') {
                $resolved = (string) $labels[$type][$id];
            }

            if ($resolved === '') {
                $resolved = AdminAuditTarget::labelFromSnapshot($type, $snapshot) ?? '';
            }

            $row['target_href'] = AdminAuditTarget::href($type, $id, $snapshot);
            $row['target_label'] = $resolved;
            $row['admin_href'] = AdminAuditTarget::adminHref(self::positiveId($row['admin_id'] ?? null));
            unset($row['target_snapshot']);
        }

        unset($row);

        return $rows;
    }

    private static function positiveId(mixed $value): ?int
    {
        if (!is_int($value) && !is_string($value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
