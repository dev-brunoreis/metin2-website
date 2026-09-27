<?php

declare(strict_types=1);

namespace Metin2Website\Tests\Unit\Repository;

use Metin2Website\Repository\EmailTokenRepository;
use Metin2Website\Support\AppCrypto;
use PHPUnit\Framework\TestCase;

final class EmailTokenRepositoryTest extends TestCase
{
    private string $previousKey;

    protected function setUp(): void
    {
        $this->previousKey = (string) ($_ENV['APP_KEY'] ?? '');
        $_ENV['APP_KEY'] = AppCrypto::generateKey();
        putenv('APP_KEY=' . $_ENV['APP_KEY']);
        \Metin2Website\Support\Env::$instance = null;
    }

    protected function tearDown(): void
    {
        if ($this->previousKey !== '') {
            $_ENV['APP_KEY'] = $this->previousKey;
            putenv('APP_KEY=' . $this->previousKey);
        } else {
            unset($_ENV['APP_KEY']);
            putenv('APP_KEY');
        }

        \Metin2Website\Support\Env::$instance = null;
    }

    public function testHashTokenIsDeterministic(): void
    {
        $hash1 = EmailTokenRepository::hashToken('abc');
        $hash2 = EmailTokenRepository::hashToken('abc');

        self::assertSame($hash1, $hash2);
        self::assertSame(64, strlen($hash1));
    }

    public function testDifferentPlainTokensProduceDifferentHashes(): void
    {
        self::assertNotSame(
            EmailTokenRepository::hashToken('one'),
            EmailTokenRepository::hashToken('two'),
        );
    }
}
