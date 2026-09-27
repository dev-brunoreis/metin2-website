<?php

declare(strict_types=1);

namespace Metin2Website\Service;

use Metin2Website\Repository\AccountRepository;
use Metin2Website\Repository\GameEconomyScanRepository;
use Metin2Website\Repository\GuildRepository;
use Metin2Website\Repository\PlayerRepository;

final class LogEnricher
{
    public function __construct(
        private LogRowPresenter $presenter,
        private PlayerRepository $players,
        private GameEconomyScanRepository $scan,
        private AccountRepository $accounts,
        private GuildRepository $guilds,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    public function decorate(string $logId, array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $need = $this->presenter->neededLookups($logId, $rows);

        return $this->presenter->decorate(
            $logId,
            $rows,
            $this->players->namesByIds($need['players']),
            $this->scan->itemNamesByVnum($need['vnums']),
            $this->accounts->loginsByIds($need['accounts']),
            $this->guilds->namesByIds($need['guilds']),
        );
    }
}
