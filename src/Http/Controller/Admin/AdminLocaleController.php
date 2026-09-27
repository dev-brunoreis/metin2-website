<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller\Admin;

use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\Http\Controller\Controller;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Locales;
use Metin2Website\I18n\Translator;
use Metin2Website\Theme\ThemeEngine;

/**
 * Admin-session locale switcher. Must POST under /admin/* so CSRF uses METIN2ADMIN
 * (path /admin), not the public METIN2WEB session used by POST /locale.
 */
class AdminLocaleController extends Controller
{
    public function __construct(
        ThemeEngine $theme,
        Auth $auth,
        Csrf $csrf,
        Translator $translator,
        private Locales $locales,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator);
    }

    public function update(): Response
    {
        $redirect = $this->locales->safeRedirect(
            is_string($_POST['redirect'] ?? null) ? $_POST['redirect'] : '/admin',
        );

        $path = explode('?', $redirect, 2)[0];

        if ($path !== '/admin' && !str_starts_with($path, '/admin/')) {
            $redirect = '/admin';
        }

        if (!$this->assertCsrf()) {
            return $this->redirect($redirect);
        }

        $locale = trim((string) ($_POST['locale'] ?? ''));

        if (!$this->locales->isSupported($locale)) {
            return $this->redirect($redirect);
        }

        return $this->redirect($redirect)->withCookie(Locales::COOKIE, $locale);
    }
}
