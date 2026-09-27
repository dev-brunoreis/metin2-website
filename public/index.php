<?php

declare(strict_types=1);

use Metin2Website\Application;

define('BASE_DIR', dirname(__DIR__));

require BASE_DIR . '/src/bootstrap/autoload.php';

(new Application())->run();
