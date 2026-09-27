<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller;

use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Translator;
use Metin2Website\Repository\EventRepository;
use Metin2Website\Repository\NewsRepository;
use Metin2Website\Service\SeoService;
use Metin2Website\Theme\ThemeEngine;

class SeoController extends Controller
{
    public function __construct(
        ThemeEngine $theme,
        Auth $auth,
        Csrf $csrf,
        Translator $translator,
        private SeoService $seo,
        private NewsRepository $news,
        private EventRepository $events,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator);
    }

    public function robots(): Response
    {
        return Response::text($this->seo->robotsTxt());
    }

    public function sitemap(): Response
    {
        return Response::xml($this->seo->sitemapXml(
            $this->news->listPublishedForSitemap(),
            $this->events->listPublishedForSitemap(),
        ));
    }
}
