<?php

declare(strict_types=1);

namespace Metin2Website\Service;

use Metin2Website\Game\Drop\GroupTextParser;
use Metin2Website\Game\Drop\LocaleText;
use Metin2Website\Game\GameProfile;

class DropFileService
{
    public function __construct(
        private GameProfile $profile,
        private GroupTextParser $parser,
    ) {
    }

    /**
     * Read-only: which drop groups mention this item vnum.
     *
     * @return list<array{source: string, group: string, mob_vnum: int|null, chance: string}>
     */
    public function sourcesForItemVnum(int $itemVnum): array
    {
        if ($itemVnum < 1) {
            return [];
        }

        $found = [];

        foreach (['mob_drop_item', 'drop_item_group'] as $fileKey) {
            foreach ($this->loadGroupFile($fileKey) as $group) {
                $mobVnum = (int) ($group['attrs']['mob'][0] ?? 0);

                foreach ($group['items'] as $row) {
                    $candidate = (int) ($row[0] ?? 0);

                    if ($candidate !== $itemVnum) {
                        $candidate = (int) ($row[1] ?? 0);
                    }

                    if ($candidate !== $itemVnum) {
                        continue;
                    }

                    $chance = (string) ($row[2] ?? ($row[3] ?? ''));
                    $found[] = [
                        'source' => $fileKey,
                        'group' => (string) ($group['name'] ?? ''),
                        'mob_vnum' => $mobVnum > 0 ? $mobVnum : null,
                        'chance' => $chance,
                    ];
                }
            }
        }

        foreach ($this->commonDrops()['ranks'] as $rank => $rows) {
            foreach ($rows as $row) {
                $ref = trim((string) ($row['item_ref'] ?? ''));

                if ($ref === '' || (int) $ref !== $itemVnum) {
                    continue;
                }

                $found[] = [
                    'source' => 'common_drop_item:' . $rank,
                    'group' => (string) ($row['label'] ?? $rank),
                    'mob_vnum' => null,
                    'chance' => (string) ($row['chance'] ?? ''),
                ];
            }
        }

        return $found;
    }

    /**
     * @return array{header: string, ranks: array<string, list<array<string, mixed>>>}
     */
    private function commonDrops(): array
    {
        $path = $this->profile->dropPath('common_drop_item');
        $ranks = [];

        foreach ($this->profile->commonRanks() as $rank) {
            $ranks[$rank] = [];
        }

        if (!is_file($path) || !is_readable($path)) {
            return ['header' => '', 'ranks' => $ranks];
        }

        $raw = file_get_contents($path);

        if ($raw === false) {
            return ['header' => '', 'ranks' => $ranks];
        }

        $lines = explode("\n", str_replace("\r\n", "\n", LocaleText::decode($raw)));
        $header = array_shift($lines) ?? '';

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cells = explode("\t", $line);

            foreach ($this->profile->commonRanks() as $index => $rank) {
                $offset = $index * 6;
                $levelStart = (int) ($cells[$offset + 1] ?? 0);
                $itemRef = trim((string) ($cells[$offset + 4] ?? ''));

                if ($levelStart < 1 || $itemRef === '') {
                    continue;
                }

                $ranks[$rank][] = [
                    'label' => trim((string) ($cells[$offset] ?? '')),
                    'level_start' => $levelStart,
                    'level_end' => (int) ($cells[$offset + 2] ?? 0),
                    'chance' => trim((string) ($cells[$offset + 3] ?? '')),
                    'item_ref' => $itemRef,
                    'one_in' => (int) ($cells[$offset + 5] ?? 0),
                ];
            }
        }

        return ['header' => $header, 'ranks' => $ranks];
    }

    /**
     * @return list<array{name: string, attrs: array<string, list<string>>, items: list<list<string>>}>
     */
    private function loadGroupFile(string $key): array
    {
        $path = $this->profile->dropPath($key);

        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        $raw = file_get_contents($path);

        if ($raw === false || trim($raw) === '') {
            return [];
        }

        return $this->parser->parse(LocaleText::decode($raw));
    }
}
