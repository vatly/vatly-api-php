<?php

declare(strict_types=1);

namespace Vatly\API\Support;

/**
 * @internal
 */
final class LoopbackHost
{
    /**
     * Whether the URL points at this machine, the only place plain HTTP is allowed.
     */
    public static function isLoopbackUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, ['localhost', '127.0.0.1', '[::1]'], true);
    }
}
