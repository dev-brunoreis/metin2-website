<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller;

use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Translator;
use Metin2Website\Repository\EventRepository;
use Metin2Website\Repository\NewsRepository;
use Metin2Website\Repository\PlayerRepository;
use Metin2Website\Service\SettingsService;
use Metin2Website\Theme\ThemeEngine;

class HomeController extends Controller
{
    public function __construct(
        ThemeEngine $theme,
        Auth $auth,
        Csrf $csrf,
        Translator $translator,
        private NewsRepository $news,
        private EventRepository $events,
        private PlayerRepository $players,
        private SettingsService $settings,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator);
    }

    public function index(): Response
    {
        $minutes = $this->settings->onlineWindowMinutes();

        return $this->view('home', [
            'title' => $this->t('nav.home'),
            'seo' => [
                'fallback_description' => $this->t('home.lead'),
            ],
            'posts' => $this->news->latestPublished(5),
            'events' => $this->events->upcomingPublished(5),
            'playersOnline' => $this->players->countActiveSinceMinutes($minutes),
            'accountsOnline' => $this->players->countAccountsActiveSinceMinutes($minutes),
            'windowMinutes' => $minutes,
        ]);
    }
}
