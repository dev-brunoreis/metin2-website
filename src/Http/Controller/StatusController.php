<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller;

use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Translator;
use Metin2Website\Repository\PlayerRepository;
use Metin2Website\Repository\ServerChannelRepository;
use Metin2Website\Service\SettingsService;
use Metin2Website\Theme\ThemeEngine;

class StatusController extends Controller
{
    public function __construct(
        ThemeEngine $theme,
        Auth $auth,
        Csrf $csrf,
        Translator $translator,
        private PlayerRepository $players,
        private ServerChannelRepository $channels,
        private SettingsService $settings,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator);
    }

    public function index(): Response
    {
        $minutes = $this->settings->onlineWindowMinutes();

        return $this->view('status', [
            'title' => $this->t('status.title'),
            'playersOnline' => $this->players->countActiveSinceMinutes($minutes),
            'accountsOnline' => $this->players->countAccountsActiveSinceMinutes($minutes),
            'windowMinutes' => $minutes,
            'channels' => $this->channels->listEnabled(),
        ]);
    }
}
