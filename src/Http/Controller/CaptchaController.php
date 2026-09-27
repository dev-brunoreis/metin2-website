<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller;

use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Captcha;
use Metin2Website\Auth\Csrf;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Translator;
use Metin2Website\Theme\ThemeEngine;

class CaptchaController extends Controller
{
    public function __construct(
        ThemeEngine $theme,
        Auth $auth,
        Csrf $csrf,
        Translator $translator,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator);
    }

    public function publicSvg(): Response
    {
        $captcha = new Captcha('public');
        $svg = $captcha->svg();

        return new Response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ], false);
    }

    public function adminSvg(): Response
    {
        $captcha = new Captcha('admin');
        $svg = $captcha->svg();

        return new Response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ], false);
    }
}
