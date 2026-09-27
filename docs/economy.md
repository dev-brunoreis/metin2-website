# Economy

Admin **Game → Economy** (`/admin/game/economy`). ACL: `game/economy/view`, `edit`, `mass`. CMS tables from migrations `018_economy.sql` + `019_economy_intelligence.sql`. Game gold/item logs stay in the `log` schema; the CMS stores census, yang daily, ingested trades, watches, and alerts.

## Tick

`php bin/economy-tick.php` → `EconomyTickService::run()` (lock `var/economy-tick.lock`).

Each run: player/safebox/guild yang census, daily yang rollup, trade ingest from gold/item logs, anomaly alerts (outlier band, Discord via `DiscordWebhookService` when configured).

`money_created` / `money_destroyed` sum `money_log` types whose `gold` column is yang: `MONSTER`, `SHOP`, `REFINE`, `QUEST`, `GUILD`, `MISC` (`EconomyStats::isYangMoneyType`). `DROP` is item count and `KILL` is mob kill count on this core, so they are excluded (`money_kill` is stored as 0). The db process flushes `money_log` about once an hour, and it drops quest rows (`type` 4) before insert.

`money_drop` is ground pickup: `SUM(log.what)` where `type=CHARACTER` and `how=GET_GOLD`. This core only writes that row when the pile is greater than 1000. It is not added on top of `money_created`, because `MONEY_LOG_MONSTER` already counts yang at drop time.

Without a frequent cron the admin KPIs go stale (UI flags last-ok older than ~2h).

## Admin UI

| Route | Job | ACL |
| --- | --- | --- |
| `GET /admin/game/economy` | Market KPIs, charts, unacked alerts, item grid | view (via `adminView('economy')`) |
| `GET …/players`, `…/players/{id}` | Per-player economy | view |
| `GET …/{id}` | Item detail | view |
| `POST …/{id}/watch` | Watch / unwatch an item | `game/economy/edit` |
| `POST …/alerts/{id}/ack` | Ack alert; optional `back` must stay under `/admin/game/economy` (`Locales::safeRedirectUnder`) | `game/economy/edit` |
| `POST …/mass` | Grid mass (`runMassActions`) | `game/economy/mass` |

Demo seed (dev): `bin/economy-seed-demo.php`.
