<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller;

use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Translator;
use Metin2Website\Support\Database;
use Metin2Website\Support\Log;
use Metin2Website\Theme\ThemeEngine;

class HealthController extends Controller
{
    public function __construct(
        ThemeEngine $theme,
        Auth $auth,
        Csrf $csrf,
        Translator $translator,
        private Database $cmsDb,
        private Database $gameDb,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator);
    }

    public function index(): Response
    {
        try {
            $this->cmsDb->fetchColumn('SELECT 1');
            $this->gameDb->fetchColumn('SELECT 1');

            return Response::html('ok', 200);
        } catch (\Throwable $e) {
            Log::error('health', 'Health check failed', $e);

            return Response::html('Service temporarily unavailable', 503);
        }
    }
}
