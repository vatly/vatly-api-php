<?php

declare(strict_types=1);

namespace Vatly\API;

use Vatly\API\Exceptions\IncompatiblePlatformException;

class CompatibilityChecker
{
    /**
     * @var string
     */
    public const MIN_PHP_VERSION = "8.0";

    /**
     * Required PHP extensions, mapped to the {@see IncompatiblePlatformException}
     * code thrown when the extension is missing. Mirrors composer's `require`
     * (`ext-bcmath`, `ext-curl`, `ext-intl`, `ext-openssl`, `ext-json`).
     *
     * `bcmath` is load-bearing for {@see \Vatly\API\Types\Money}'s arithmetic.
     *
     * @var array<string, int>
     */
    public const REQUIRED_EXTENSIONS = [
        'bcmath' => IncompatiblePlatformException::INCOMPATIBLE_BCMATH_EXTENSION,
        'curl' => IncompatiblePlatformException::INCOMPATIBLE_CURL_EXTENSION,
        'intl' => IncompatiblePlatformException::INCOMPATIBLE_INTL_EXTENSION,
        'openssl' => IncompatiblePlatformException::INCOMPATIBLE_OPENSSL_EXTENSION,
        'json' => IncompatiblePlatformException::INCOMPATIBLE_JSON_EXTENSION,
    ];

    /**
     * @return void
     * @throws IncompatiblePlatformException
     */
    public function checkCompatibility()
    {
        if (! $this->satisfiesPhpVersion()) {
            throw new IncompatiblePlatformException(
                "The client requires PHP version >= " . self::MIN_PHP_VERSION . ", you have " . PHP_VERSION . ".",
                IncompatiblePlatformException::INCOMPATIBLE_PHP_VERSION
            );
        }

        foreach (self::REQUIRED_EXTENSIONS as $extension => $code) {
            if (! $this->satisfiesExtension($extension)) {
                throw new IncompatiblePlatformException(
                    "PHP extension {$extension} is not enabled. Please make sure to enable '{$extension}' in your PHP configuration.",
                    $code
                );
            }
        }
    }

    /**
     * @return bool
     * @codeCoverageIgnore
     */
    public function satisfiesPhpVersion(): bool
    {
        return (bool)version_compare(PHP_VERSION, self::MIN_PHP_VERSION, ">=");
    }

    /**
     * @return bool
     * @codeCoverageIgnore
     */
    public function satisfiesExtension(string $extension): bool
    {
        return function_exists('extension_loaded') && extension_loaded($extension);
    }
}
