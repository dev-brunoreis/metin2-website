<?php

declare(strict_types=1);

namespace Metin2Website\Auth;

use Metin2Website\Support\Env;

/**
 * Derives PHP session cookie name and path from the request URI.
 */
final class SessionConfig
{
    public const PUBLIC_NAME = 'METIN2WEB';
    public const ADMIN_NAME = 'METIN2ADMIN';

    public static function forRequestUri(string $uri): self
    {
        $path = $uri;

        if (false !== $pos = strpos($uri, '?')) {
            $path = substr($uri, 0, $pos);
        }

        $path = rawurldecode($path);

        if ($path === '/admin' || str_starts_with($path, '/admin/')) {
            return new self(self::ADMIN_NAME, '/admin');
        }

        return new self(self::PUBLIC_NAME, '/');
    }

    public function __construct(
        public readonly string $name,
        public readonly string $path,
    ) {
    }

    public static function isSecureRequest(): bool
    {
        $https = $_SERVER['HTTPS'] ?? '';

        if ($https !== '' && $https !== 'off') {
            return true;
        }

        if (!self::trustsProxy()) {
            return false;
        }

        $proto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));

        return $proto === 'https';
    }

    private static function trustsProxy(): bool
    {
        $value = Env::getInstance()->get('APP_TRUST_PROXY', '0');

        return in_array(strtolower((string) $value), ['1', 'true', 'yes'], true);
    }
}
