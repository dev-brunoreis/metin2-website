<?php

declare(strict_types=1);

namespace Metin2Website\Admin;

final class AdminAuditTarget
{
    /**
     * @param array<string, mixed> $snapshot
     */
    public static function href(string $type, ?int $id, array $snapshot = []): ?string
    {
        $id = $id !== null && $id > 0 ? $id : null;

        return match ($type) {
            'account' => $id !== null ? '/admin/game/accounts/' . $id : AdminPaths::gameAccounts(),
            'player' => $id !== null ? '/admin/game/characters/' . $id : AdminPaths::gameCharacters(),
            'admin' => $id !== null ? '/admin/system/admins/' . $id : AdminPaths::systemAdmins(),
            'guild' => $id !== null ? '/admin/game/guilds/' . $id : AdminPaths::gameGuilds(),
            'guild_comment' => self::guildCommentHref($snapshot),
            'event' => $id !== null ? AdminPaths::contentEvent($id) : AdminPaths::contentEvents(),
            'news' => $id !== null ? AdminPaths::contentNewsPost($id) : AdminPaths::contentNews(),
            'news_comment' => AdminPaths::contentNews('comments'),
            'ticket' => $id !== null ? '/admin/content/tickets/' . $id : AdminPaths::contentTickets(),
            'shop' => $id !== null ? '/admin/game-data/shops/' . $id : AdminPaths::gameDataShops(),
            'refine' => $id !== null ? '/admin/game-data/refine/' . $id : AdminPaths::gameDataRefine(),
            'proto_items' => $id !== null ? '/admin/game-data/items/' . $id : AdminPaths::gameDataItems(),
            'proto_mobs' => $id !== null ? '/admin/game-data/mobs/' . $id : AdminPaths::gameDataMobs(),
            'package' => $id !== null ? '/admin/store/packages/' . $id : AdminPaths::storePackages(),
            'payment' => $id !== null ? '/admin/store/payments/' . $id : AdminPaths::storePayments(),
            'banner' => $id !== null ? AdminPaths::contentBanner($id) : AdminPaths::contentBanners(),
            'download' => $id !== null ? '/admin/content/downloads/' . $id : AdminPaths::contentDownloads(),
            'item_shop_product' => $id !== null ? AdminPaths::storeProduct($id) : AdminPaths::storeProducts(),
            'item_shop_category' => $id !== null ? AdminPaths::storeCategoryEdit($id) : AdminPaths::store('categories'),
            'gm' => $id !== null ? '/admin/game-data/gms/' . $id : AdminPaths::gameDataGms(),
            'gm_host' => AdminPaths::gameDataGms(),
            'economy_item' => $id !== null ? AdminPaths::gameEconomyItem($id) : AdminPaths::gameEconomy(),
            'economy_alert' => AdminPaths::gameEconomy(),
            'award' => AdminPaths::gameAwards(),
            'ban' => AdminPaths::gameBans(),
            'admin_role' => self::roleHref($snapshot),
            'settings' => AdminPaths::settings(),
            'news_settings' => AdminPaths::settingsNews(),
            'banner_settings' => AdminPaths::settingsBanners(),
            'server_channel' => AdminPaths::settingsChannels(),
            default => null,
        };
    }

    public static function adminHref(?int $adminId): ?string
    {
        if ($adminId === null || $adminId < 1) {
            return null;
        }

        return '/admin/system/admins/' . $adminId;
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    public static function labelFromSnapshot(string $type, array $snapshot): ?string
    {
        $keys = self::labelKeys($type);
        $buckets = [];

        if (isset($snapshot['after']) && is_array($snapshot['after'])) {
            $buckets[] = $snapshot['after'];
        }

        if (isset($snapshot['before']) && is_array($snapshot['before'])) {
            $buckets[] = $snapshot['before'];
        }

        $buckets[] = $snapshot;

        foreach ($buckets as $bucket) {
            foreach ($keys as $key) {
                $value = self::scalar($bucket, $key);

                if ($value !== null && $value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    private static function guildCommentHref(array $snapshot): string
    {
        $guildId = self::intValue($snapshot, 'guild_id');

        if ($guildId !== null) {
            return '/admin/game/guilds/' . $guildId;
        }

        return AdminPaths::gameGuilds();
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    private static function roleHref(array $snapshot): string
    {
        $slug = self::scalar($snapshot, 'slug')
            ?? self::scalar($snapshot['after'] ?? [], 'slug')
            ?? self::scalar($snapshot['before'] ?? [], 'slug');

        if ($slug !== null && preg_match('/^[a-z0-9-]+$/', $slug) === 1) {
            return '/admin/system/roles/' . $slug;
        }

        return AdminPaths::systemRoles();
    }

    /**
     * @return list<string>
     */
    private static function labelKeys(string $type): array
    {
        $preferred = match ($type) {
            'account', 'admin' => ['login'],
            'player', 'guild', 'shop' => ['name'],
            'news', 'event', 'banner', 'download', 'package' => ['title'],
            'ticket' => ['subject'],
            'gm' => ['mAccount'],
            'admin_role' => ['slug', 'label'],
            'ban' => ['login', 'account_login'],
            'item_shop_category' => ['name'],
            default => [],
        };

        return array_values(array_unique([
            ...$preferred,
            'login',
            'name',
            'title',
            'subject',
            'slug',
            'label',
            'mAccount',
            'account_login',
        ]));
    }

    /**
     * @param array<string|int, mixed> $values
     */
    private static function scalar(array $values, string $key): ?string
    {
        if (!array_key_exists($key, $values)) {
            return null;
        }

        $value = $values[$key];

        if (is_string($value) || is_int($value) || is_float($value)) {
            $text = trim((string) $value);

            return $text !== '' ? $text : null;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $values
     */
    private static function intValue(array $values, string $key): ?int
    {
        $raw = $values[$key] ?? ($values['after'][$key] ?? ($values['before'][$key] ?? null));

        if (!is_int($raw) && !is_string($raw)) {
            return null;
        }

        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }
}
