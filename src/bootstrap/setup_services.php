<?php

declare(strict_types=1);

use Metin2Website\Application;
use Metin2Website\Auth\Auth;
use Metin2Website\I18n\Translator;
use Metin2Website\Repository\AccountRepository;
use Metin2Website\Support\Database;

/**
 * @return callable(Application): void
 */
return static function (Application $app): void {
    $app->translator = new Translator(BASE_DIR . '/lang', $app->locales->resolve('en'));
    $app->theme = $app->createThemeEngine('default', true, false);
    $app->auth = new Auth(new AccountRepository(new Database(['requirePassword' => false])));
};
