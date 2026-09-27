<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller;

use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Translator;
use Metin2Website\Repository\DownloadRepository;
use Metin2Website\Service\DownloadUploadService;
use Metin2Website\Theme\ThemeEngine;

class DownloadsController extends Controller
{
    public function __construct(
        ThemeEngine $theme,
        Auth $auth,
        Csrf $csrf,
        Translator $translator,
        private DownloadRepository $downloads,
        private DownloadUploadService $uploads,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator);
    }

    public function index(): Response
    {
        return $this->view('downloads', [
            'title' => $this->t('downloads.title'),
            'downloads' => $this->downloads->listPublic(),
        ]);
    }

    public function file(string $id): Response
    {
        $row = $this->downloads->findById((int) $id);

        if ($row === null || !(int) ($row['enabled'] ?? 0)) {
            return Response::notFound($this->t('downloads.not_found'));
        }

        $external = trim((string) ($row['external_url'] ?? ''));

        if ($external !== '') {
            return Response::redirect($external);
        }

        $stored = trim((string) ($row['stored_name'] ?? ''));

        if ($stored === '') {
            return Response::notFound($this->t('downloads.not_found'));
        }

        try {
            $path = $this->uploads->absolutePath($stored);
        } catch (\InvalidArgumentException) {
            return Response::notFound($this->t('downloads.not_found'));
        }

        if (!is_file($path)) {
            return Response::notFound($this->t('downloads.not_found'));
        }

        $name = (string) ($row['original_name'] ?? 'download.bin');

        return Response::download($path, $name, 'application/octet-stream');
    }
}
