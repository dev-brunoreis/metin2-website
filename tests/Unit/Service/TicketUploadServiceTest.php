<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Service;

use Metin2Website\Service\TicketUploadService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class TicketUploadServiceTest extends TestCase
{
    private TicketUploadService $uploads;

    protected function setUp(): void
    {
        $this->uploads = new TicketUploadService(sys_get_temp_dir() . '/metin2website-tickets');
    }

    public function testAbsolutePathRejectsInvalidName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->uploads->absolutePath('../../../etc/passwd');
    }

    public function testAbsolutePathRejectsWrongExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->uploads->absolutePath(str_repeat('a', 32) . '.exe');
    }

    public function testAbsolutePathAcceptsValidStoredName(): void
    {
        $name = str_repeat('a', 32) . '.jpg';
        $path = $this->uploads->absolutePath($name);

        self::assertStringEndsWith('/' . $name, $path);
    }

    public function testSafeOriginalNameSanitizesUnsafeInput(): void
    {
        $method = new ReflectionMethod(TicketUploadService::class, 'safeOriginalName');
        $result = $method->invoke($this->uploads, "../../evil\x00name.png", 'png');

        self::assertSame('evilname.png', $result);
        self::assertStringEndsWith('.png', $result);
    }
}
