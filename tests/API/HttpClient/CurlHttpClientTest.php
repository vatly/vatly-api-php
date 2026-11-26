<?php

declare(strict_types=1);

namespace Vatly\Tests\API\HttpClient;

use PHPUnit\Framework\TestCase;
use Vatly\API\Exceptions\ApiException;
use Vatly\API\Exceptions\AuthenticationException;
use Vatly\API\Exceptions\RateLimitException;
use Vatly\API\Exceptions\ValidationException;
use Vatly\API\HttpClient\CurlHttpClient;

/**
 * Sends real requests through cURL to a PHP built-in web server on 127.0.0.1,
 * which answers each path with a fixed response (see Fixtures/curl-test-server.php).
 */
class CurlHttpClientTest extends TestCase
{
    /** @var resource|null */
    private static $server;

    private static string $baseUrl;

    public static function setUpBeforeClass(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) substr(strrchr((string) stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);

        $router = __DIR__ . '/Fixtures/curl-test-server.php';
        self::$server = proc_open(
            [PHP_BINARY, '-S', "127.0.0.1:{$port}", $router],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes
        );
        self::$baseUrl = "http://127.0.0.1:{$port}";

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $connection = @fsockopen('127.0.0.1', $port);

            if ($connection !== false) {
                fclose($connection);

                return;
            }

            usleep(50000);
        }

        self::fail('The PHP built-in web server did not start.');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$server !== null) {
            proc_terminate(self::$server);
            proc_close(self::$server);
            self::$server = null;
        }
    }

    /** @test */
    public function aSuccessfulResponseIsDecoded(): void
    {
        $body = (new CurlHttpClient)->send('GET', self::$baseUrl . '/v1/ok', [], null);

        $this->assertSame('checkout_123', $body->id);
    }

    /** @test */
    public function noContentReturnsNull(): void
    {
        $this->assertNull((new CurlHttpClient)->send('DELETE', self::$baseUrl . '/v1/no-content', [], null));
    }

    /** @test */
    public function aValidationErrorKeepsTheFieldErrorsAndLeavesTheRequestBodyOutOfTheMessage(): void
    {
        $requestBody = '{"email":"jane.doe@example.com","metadata":{"ticket":"secret-ticket-42"}}';

        try {
            (new CurlHttpClient)->send('POST', self::$baseUrl . '/v1/validation', [], $requestBody);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertSame('The email field must be a valid email address.', $exception->getMessage());
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertSame(['email' => ['The email field must be a valid email address.']], $exception->getErrors());
            $this->assertStringNotContainsString('jane.doe@example.com', $exception->getMessage());
            $this->assertStringNotContainsString('secret-ticket-42', $exception->getMessage());
        }
    }

    /** @test */
    public function aRateLimitResponseCarriesRetryAfterFromTheHeader(): void
    {
        try {
            (new CurlHttpClient)->send('POST', self::$baseUrl . '/v1/rate-limited', [], '{}');
            $this->fail('Expected a RateLimitException.');
        } catch (RateLimitException $exception) {
            $this->assertSame(429, $exception->getStatusCode());
            $this->assertSame(30, $exception->getRetryAfter());
            $this->assertSame('Too Many Attempts.', $exception->getMessage());
        }
    }

    /** @test */
    public function anErrorStatusWithAnEmptyBodyStillMapsToItsSubclass(): void
    {
        try {
            (new CurlHttpClient)->send('GET', self::$baseUrl . '/v1/unauthorized-empty', [], null);
            $this->fail('Expected an AuthenticationException.');
        } catch (AuthenticationException $exception) {
            $this->assertSame(401, $exception->getStatusCode());
            $this->assertSame('The Vatly API responded with HTTP status 401.', $exception->getMessage());
        }
    }

    /** @test */
    public function anHtmlErrorPageKeepsItsBodyOutOfTheMessage(): void
    {
        try {
            (new CurlHttpClient)->send('GET', self::$baseUrl . '/v1/bad-gateway', [], null);
            $this->fail('Expected an ApiException.');
        } catch (ApiException $exception) {
            $this->assertSame(ApiException::class, get_class($exception));
            $this->assertSame(502, $exception->getStatusCode());
            $this->assertSame('The Vatly API responded with HTTP status 502.', $exception->getMessage());
            $this->assertSame('<html><body>Bad Gateway</body></html>', $exception->getResponseBody());
        }
    }

    /** @test */
    public function aSuccessStatusWithABodyThatIsNotJsonIsRefused(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Unable to decode the Vatly API response (HTTP status 200).');

        (new CurlHttpClient)->send('GET', self::$baseUrl . '/v1/not-json', [], null);
    }
}
