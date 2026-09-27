<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller;

use Metin2Website\Http\Response;
use Metin2Website\Theme\ThemeAssetFile;

class ThemeAssetController extends Controller
{
    public function show(string $theme, string $asset): Response
    {
        $path = ThemeAssetFile::absolutePath(BASE_DIR . '/themes', $theme, rawurldecode($asset));

        if ($path === null) {
            return Response::notFound('');
        }

        return Response::cachedFile($path, ThemeAssetFile::mimeType($asset));
    }
}
