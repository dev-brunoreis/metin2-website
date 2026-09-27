#!/usr/bin/env php
<?php

declare(strict_types=1);

define('BASE_DIR', dirname(__DIR__));

require BASE_DIR . '/src/bootstrap/autoload.php';

Metin2Website\Application::loadConfigs();

try {
    if (!Metin2Website\Support\AppCrypto::hasValidKey()) {
        $writer = new Metin2Website\Setup\EnvWriter();
        $writer->upsert(
            ['APP_KEY' => Metin2Website\Support\AppCrypto::generateKey()],
            BASE_DIR . '/.env',
        );
        echo "Generated APP_KEY in .env\n";
    }

    $db = Metin2Website\Support\Database::forCms();
    $runner = new Metin2Website\Setup\MigrationRunner($db);
    $before = $runner->currentVersion();
    $schema = new Metin2Website\Setup\CmsSchema($db);
    $schema->ensure();
    $schema->seedDefaults(array_merge([
        'news_comments_enabled' => '1',
        'news_comments_require_approval' => '0',
    ], Metin2Website\Setup\CmsSchema::defaultSecuritySettings()));
    $after = $runner->currentVersion();

    echo 'CMS schema migrated: ' . $before . ' -> ' . $after . PHP_EOL;
} catch (\PDOException $e) {
    fwrite(STDERR, "CMS database connection failed.\n");
    fwrite(STDERR, "From the host, set CMS_DB_HOST=127.0.0.1 and CMS_DB_PORT=8002 in .env.\n");
    fwrite(STDERR, "Inside Docker, use CMS_DB_HOST=mysql (default) and run:\n");
    fwrite(STDERR, "  docker compose exec php php bin/migrate.php\n");
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
