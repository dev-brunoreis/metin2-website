<?php

declare(strict_types=1);

namespace Metin2Website\Game;

use Metin2Website\Support\Database;

/**
 * Elapsed time for naive DATETIME values written by the game MySQL.
 * The CMS host timezone does not matter: the string is read as UTC and
 * shifted by the game session offset and that server's own UNIX_TIMESTAMP(NOW()).
 */
final class GameClock
{
    private bool $loaded = false;

    private ?int $unixNow = null;

    private int $sessionOffset = 0;

    public function __construct(
        private ?Database $gameDb = null,
    ) {
    }

    public static function fixed(int $unixNow, int $sessionOffset = 0): self
    {
        $clock = new self(null);
        $clock->loaded = true;
        $clock->unixNow = $unixNow;
        $clock->sessionOffset = $sessionOffset;

        return $clock;
    }

    /**
     * Seconds since $mysqlDatetime on the game clock. Null when the clock
     * cannot be read or the value is empty.
     */
    public function elapsedSeconds(string $mysqlDatetime): ?int
    {
        if (!$this->load()) {
            return null;
        }

        $raw = trim($mysqlDatetime);

        if ($raw === '' || str_starts_with($raw, '0000-00-00')) {
            return null;
        }

        try {
            $parsed = new \DateTimeImmutable($raw, new \DateTimeZone('UTC'));
        } catch (\Exception) {
            return null;
        }

        return $this->unixNow - $parsed->getTimestamp() + $this->sessionOffset;
    }

    /**
     * True when $mysqlDatetime is inside the recent-activity window on the
     * game clock (same rule as population / unstuck).
     */
    public function isRecentlyActive(string $mysqlDatetime, int $minutes): bool
    {
        $elapsed = $this->elapsedSeconds($mysqlDatetime);

        return $elapsed !== null && $elapsed <= max(1, $minutes) * 60;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    public function markOnline(array $rows, int $minutes, string $field = 'last_play'): array
    {
        foreach ($rows as $i => $row) {
            $rows[$i]['online'] = $this->isRecentlyActive((string) ($row[$field] ?? ''), $minutes);
        }

        return $rows;
    }

    private function load(): bool
    {
        if ($this->loaded) {
            return $this->unixNow !== null;
        }

        $this->loaded = true;

        if ($this->gameDb === null) {
            return false;
        }

        try {
            $row = $this->gameDb->fetch(
                'SELECT UNIX_TIMESTAMP(NOW()) AS unix_now, TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), NOW()) AS session_offset',
            );
        } catch (\Throwable) {
            return false;
        }

        if ($row === null) {
            return false;
        }

        $this->unixNow = (int) ($row['unix_now'] ?? 0);
        $this->sessionOffset = (int) ($row['session_offset'] ?? 0);

        return $this->unixNow > 0;
    }
}
