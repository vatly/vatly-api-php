<?php

declare(strict_types=1);

namespace Vatly\Tests\API;

use PHPUnit\Framework\TestCase;
use Vatly\API\CompatibilityChecker;
use Vatly\API\Exceptions\IncompatiblePlatformException;

class CompatibilityCheckerTest extends TestCase
{
    public function test_min_php_version_matches_composer_constraint(): void
    {
        $this->assertSame('8.0', CompatibilityChecker::MIN_PHP_VERSION);
    }

    public function test_required_extensions_mirror_composer_require(): void
    {
        $this->assertSame(
            ['bcmath', 'curl', 'intl', 'openssl', 'json'],
            array_keys(CompatibilityChecker::REQUIRED_EXTENSIONS),
        );
    }

    public function test_it_passes_on_a_fully_compatible_platform(): void
    {
        $checker = new CompatibilityChecker();

        $checker->checkCompatibility();

        $this->expectNotToPerformAssertions();
    }

    public function test_it_throws_when_the_php_version_is_too_low(): void
    {
        $checker = new class() extends CompatibilityChecker {
            public function satisfiesPhpVersion(): bool
            {
                return false;
            }
        };

        try {
            $checker->checkCompatibility();
            $this->fail('Expected IncompatiblePlatformException was not thrown.');
        } catch (IncompatiblePlatformException $e) {
            $this->assertSame(IncompatiblePlatformException::INCOMPATIBLE_PHP_VERSION, $e->getCode());
        }
    }

    /**
     * @dataProvider missingExtensionProvider
     */
    public function test_it_throws_the_matching_code_for_each_missing_extension(string $missing, int $expectedCode): void
    {
        $checker = new class($missing) extends CompatibilityChecker {
            public function __construct(private string $missing)
            {
            }

            public function satisfiesExtension(string $extension): bool
            {
                return $extension !== $this->missing;
            }
        };

        try {
            $checker->checkCompatibility();
            $this->fail('Expected IncompatiblePlatformException was not thrown.');
        } catch (IncompatiblePlatformException $e) {
            $this->assertSame($expectedCode, $e->getCode());
            $this->assertStringContainsString($missing, $e->getMessage());
        }
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function missingExtensionProvider(): array
    {
        return [
            'bcmath' => ['bcmath', IncompatiblePlatformException::INCOMPATIBLE_BCMATH_EXTENSION],
            'curl' => ['curl', IncompatiblePlatformException::INCOMPATIBLE_CURL_EXTENSION],
            'intl' => ['intl', IncompatiblePlatformException::INCOMPATIBLE_INTL_EXTENSION],
            'openssl' => ['openssl', IncompatiblePlatformException::INCOMPATIBLE_OPENSSL_EXTENSION],
            'json' => ['json', IncompatiblePlatformException::INCOMPATIBLE_JSON_EXTENSION],
        ];
    }
}
