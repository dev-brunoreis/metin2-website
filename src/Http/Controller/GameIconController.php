<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller;

use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Translator;
use Metin2Website\Service\GameIconService;
use Metin2Website\Theme\ThemeEngine;

class GameIconController extends Controller
{
    public function __construct(
        ThemeEngine $theme,
        Auth $auth,
        Csrf $csrf,
        Translator $translator,
        private GameIconService $icons,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator);
    }

    public function show(string $kind, string $id): Response
    {
        if ($kind !== GameIconService::KIND_ITEM && $kind !== GameIconService::KIND_FACE) {
            return Response::notFound('');
        }

        $png = $this->icons->png($kind, (int) $id);

        if ($png === null) {
            return new Response('', 404, ['Content-Type' => 'image/png']);
        }

        return Response::png($png);
    }
}
