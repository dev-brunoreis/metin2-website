<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Service;

use Metin2Website\Repository\BannerRepository;
use Metin2Website\Repository\SettingsRepository;
use Metin2Website\Service\BannerSeedService;
use Metin2Website\Service\BannerUploadService;
use PHPUnit\Framework\TestCase;

final class BannerSeedServiceTest extends TestCase
{
    public function testSeedsGeneratedJpegPlaceholdersWhenEmpty(): void
    {
        if (!extension_loaded('gd')) {
            self::markTestSkipped('gd extension required');
        }

        $banners = $this->createMock(BannerRepository::class);
        $uploads = $this->createMock(BannerUploadService::class);
        $settings = $this->createMock(SettingsRepository::class);

        $settings->method('get')->with('banners_seeded')->willReturn(null);
        $banners->method('countAll')->willReturn(0);

        $titles = [];
        $uploads->expects(self::exactly(4))
            ->method('storeFromPath')
            ->willReturnCallback(function (string $path) use (&$titles): array {
                self::assertFileExists($path);
                $info = getimagesize($path);
                self::assertIsArray($info);
                self::assertSame('image/jpeg', $info['mime'] ?? null);

                return ['original_path' => '/uploads/banners/placeholder.jpg', 'variants' => []];
            });

        $banners->expects(self::exactly(4))
            ->method('create')
            ->willReturnCallback(function (array $data) use (&$titles): int {
                $titles[] = $data['title'];
                self::assertTrue($data['enabled']);
                self::assertNull($data['link_url']);

                return 1;
            });

        $settings->expects(self::once())->method('set')->with('banners_seeded', '1');

        (new BannerSeedService($banners, $uploads, $settings))->seedIfNeeded();

        self::assertSame(['Warrior', 'Ninja', 'Sura', 'Shaman'], $titles);
    }
}
