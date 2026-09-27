<?php

declare(strict_types=1);

namespace Metin2Website\Service;

use Metin2Website\Repository\BannerRepository;
use Metin2Website\Repository\SettingsRepository;

/**
 * Seeds four plain class slides once (empty table + flag).
 * Images are generated here. No game art ships with the project.
 */
class BannerSeedService
{
    /** @var list<array{title: string, alt: string, sort_order: int, color: string}> */
    private const DEFAULTS = [
        ['title' => 'Warrior', 'alt' => 'Warrior', 'sort_order' => 10, 'color' => '#3d4f3a'],
        ['title' => 'Ninja', 'alt' => 'Ninja', 'sort_order' => 20, 'color' => '#2c3340'],
        ['title' => 'Sura', 'alt' => 'Sura', 'sort_order' => 30, 'color' => '#4a3030'],
        ['title' => 'Shaman', 'alt' => 'Shaman', 'sort_order' => 40, 'color' => '#2e4044'],
    ];

    public function __construct(
        private BannerRepository $banners,
        private BannerUploadService $uploads,
        private SettingsRepository $settings,
    ) {
    }

    public function seedIfNeeded(): void
    {
        if ($this->settings->get('banners_seeded') === '1') {
            return;
        }

        if ($this->banners->countAll() > 0) {
            $this->settings->set('banners_seeded', '1');

            return;
        }

        if (!extension_loaded('gd')) {
            return;
        }

        $seeded = 0;

        foreach (self::DEFAULTS as $slide) {
            $source = $this->writePlaceholder($slide['title'], $slide['color']);

            if ($source === null) {
                return;
            }

            try {
                $stored = $this->uploads->storeFromPath($source);
                $this->banners->create([
                    'title' => $slide['title'],
                    'alt' => $slide['alt'],
                    'link_url' => null,
                    'original_path' => $stored['original_path'],
                    'variants' => $stored['variants'],
                    'sort_order' => $slide['sort_order'],
                    'enabled' => true,
                ]);
                $seeded++;
            } catch (\Throwable) {
                return;
            } finally {
                if (is_file($source)) {
                    unlink($source);
                }
            }
        }

        if ($seeded > 0) {
            $this->settings->set('banners_seeded', '1');
        }
    }

    /**
     * Solid-color JPEG with the class name. Original artwork, not a game screenshot.
     */
    private function writePlaceholder(string $title, string $hex): ?string
    {
        $width = 1920;
        $height = 640;
        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            return null;
        }

        $rgb = sscanf($hex, '#%02x%02x%02x');

        if (!is_array($rgb) || count($rgb) !== 3) {
            return null;
        }

        $background = imagecolorallocate($image, (int) $rgb[0], (int) $rgb[1], (int) $rgb[2]);
        imagefilledrectangle($image, 0, 0, $width, $height, $background);

        $font = 5;
        $textWidth = imagefontwidth($font) * strlen($title);
        $textHeight = imagefontheight($font);
        $label = imagecreatetruecolor($textWidth, $textHeight);

        if ($label === false) {
            return null;
        }

        $labelBackground = imagecolorallocate($label, (int) $rgb[0], (int) $rgb[1], (int) $rgb[2]);
        $ink = imagecolorallocate($label, 244, 239, 228);
        imagefilledrectangle($label, 0, 0, $textWidth, $textHeight, $labelBackground);
        imagestring($label, $font, 0, 0, $title, $ink);

        $scale = 8;
        $destWidth = $textWidth * $scale;
        $destHeight = $textHeight * $scale;
        imagecopyresampled(
            $image,
            $label,
            (int) (($width - $destWidth) / 2),
            (int) (($height - $destHeight) / 2),
            0,
            0,
            $destWidth,
            $destHeight,
            $textWidth,
            $textHeight,
        );
        unset($label);

        $path = tempnam(sys_get_temp_dir(), 'banner');

        if ($path === false) {
            return null;
        }

        $written = imagejpeg($image, $path, 85);
        unset($image);

        if (!$written) {
            unlink($path);

            return null;
        }

        return $path;
    }
}
