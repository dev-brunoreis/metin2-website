<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Http;

use Metin2Website\Http\Response;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ResponseTest extends TestCase
{
    public function testDefaultResponsesIncludePoweredByHeader(): void
    {
        $headers = $this->headersOf(Response::html('ok'));

        self::assertSame(
            'metin2-website - github.com/dev-brunoreis',
            $headers['X-Powered-By'],
        );
    }

    public function testJsonResponsesIncludePoweredByHeader(): void
    {
        $headers = $this->headersOf(Response::json(['ok' => true]));

        self::assertSame(
            'metin2-website - github.com/dev-brunoreis',
            $headers['X-Powered-By'],
        );
    }

    public function testWithHeaderKeepsPoweredByHeader(): void
    {
        $headers = $this->headersOf(
            Response::html('ok')->withHeader('Cache-Control', 'no-store'),
        );

        self::assertSame(
            'metin2-website - github.com/dev-brunoreis',
            $headers['X-Powered-By'],
        );
        self::assertSame('no-store', $headers['Cache-Control']);
    }

    /**
     * @return array<string, string>
     */
    private function headersOf(Response $response): array
    {
        $property = new ReflectionProperty(Response::class, 'headers');

        /** @var array<string, string> $headers */
        $headers = $property->getValue($response);

        return $headers;
    }
}
