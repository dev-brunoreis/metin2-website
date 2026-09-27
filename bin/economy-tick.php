#!/usr/bin/env php
<?php

declare(strict_types=1);

define('BASE_DIR', dirname(__DIR__));

require BASE_DIR . '/src/bootstrap/autoload.php';

Metin2Website\Application::loadConfigs();

try {
    $gameDb = new Metin2Website\Support\Database();
    $cmsDb = Metin2Website\Support\Database::forCms();

    $scan = new Metin2Website\Repository\GameEconomyScanRepository($gameDb);
    $economy = new Metin2Website\Repository\EconomyRepository($cmsDb);
    $settings = new Metin2Website\Service\SettingsService(
        new Metin2Website\Repository\SettingsRepository($cmsDb),
        new Metin2Website\Setup\ThemeCatalog(BASE_DIR . '/themes'),
    );
    $discord = new Metin2Website\Service\DiscordWebhookService($settings);
    $tick = new Metin2Website\Service\EconomyTickService($scan, $economy, $discord, BASE_DIR);

    $result = $tick->run();

    if ($result['skipped']) {
        echo "Economy tick skipped (lock held).\n";
        exit(0);
    }

    echo sprintf(
        "Economy tick OK: census=%d trades=%d unmatched=%d alerts=%d\n",
        $result['census_rows'],
        $result['trades_ingested'],
        $result['trades_unmatched'],
        $result['alerts_created'],
    );
} catch (\PDOException $e) {
    fwrite(STDERR, "Database connection failed.\n");
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Economy tick failed: ' . $e->getMessage() . "\n");
    exit(1);
}
