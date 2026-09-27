<?php

declare(strict_types=1);

namespace Mt2Cms\Game;

use Mt2Cms\Support\Database;

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
