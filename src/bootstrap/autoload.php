<?php

declare(strict_types=1);

if (!defined('BASE_DIR')) {
    throw new \LogicException('BASE_DIR must be defined before loading Composer autoload');
}

require BASE_DIR . '/src/Support/ComposerAutoload.php';

Metin2Website\Support\ComposerAutoload::load(BASE_DIR);
