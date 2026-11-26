<?php

declare(strict_types=1);

namespace Vatly\Tests\API\Exceptions;

use PHPUnit\Framework\TestCase;
use Vatly\API\Exceptions\ApiException;
use Vatly\API\Exceptions\AuthenticationException;
use Vatly\API\Exceptions\ConflictException;
use Vatly\API\Exceptions\ForbiddenException;
use Vatly\API\Exceptions\InvalidSignatureException;
use Vatly\API\Exceptions\NotFoundException;
use Vatly\API\Exceptions\RateLimitException;
use Vatly\API\Exceptions\ValidationException;
use Vatly\API\Exceptions\VatlyException;
use Vatly\API\Exceptions\WebhookSecretNotConfiguredException;

class ApiExceptionTest extends TestCase
{
    /** @test */
    public function eachDocumentedStatusMapsToItsOwnSubclassAndOthersStayApiException(): void
    {
        $expected = [
            400 => ApiException::class,
            401 => AuthenticationException::class,
            403 => ForbiddenException::class,
            404 => NotFoundException::class,
            409 => ConflictException::class,
            422 => ValidationException::class,
            429 => RateLimitException::class,
            500 => ApiException::class,
        ];

        foreach ($expected as $status => $class) {
            $exception = ApiException::fromResponse($status, '{"message":"Nope."}');

            $this->assertSame($class, get_class($exception), "HTTP {$status}");
            $this->assertSame($status, $exception->getStatusCode());
            $this->assertSame($status, $exception->getCode());
        }
    }

    /** @test */
    public function aValidationErrorCarriesTheApiMessageAndTheErrorsPerField(): void
    {
        $body = '{"message":"The email field is required. (and 1 more error)","errors":{"email":["The email field is required."],"products.0.id":["The selected products.0.id is invalid."]}}';

        $exception = ApiException::fromResponse(422, $body);

        $this->assertSame('The email field is required. (and 1 more error)', $exception->getMessage());
        $this->assertSame([
            'email' => ['The email field is required.'],
            'products.0.id' => ['The selected products.0.id is invalid.'],
        ], $exception->getErrors());
        $this->assertSame([], $exception->getDetails());
        $this->assertSame($body, $exception->getResponseBody());
    }

    /** @test */
    public function detailsAndRetryAfterAreReadFromAConflict(): void
    {
        $exception = ApiException::fromResponse(
            409,
            '{"message":"A request with this idempotency key is in progress.","details":{"idempotency_outcome":"in_progress"}}',
            ['RETRY-AFTER' => '2'],
        );

        $this->assertSame(['idempotency_outcome' => 'in_progress'], $exception->getDetails());
        $this->assertSame(2, $exception->getRetryAfter());
        $this->assertSame([], $exception->getErrors());
    }

    /** @test */
    public function retryAfterAcceptsAnHttpDateAndIsNullWithoutTheHeader(): void
    {
        $inOneMinute = gmdate('D, d M Y H:i:s \G\M\T', time() + 60);

        $retryAfter = ApiException::fromResponse(429, '{"message":"Too Many Attempts."}', ['retry-after' => $inOneMinute])->getRetryAfter();

        $this->assertGreaterThanOrEqual(58, $retryAfter);
        $this->assertLessThanOrEqual(60, $retryAfter);
        $this->assertNull(ApiException::fromResponse(429, '{"message":"Too Many Attempts."}')->getRetryAfter());
    }

    /** @test */
    public function aBodyWithoutAMessageGetsAMessageNamingTheStatus(): void
    {
        foreach (['', '{"details":{"a":1}}', '<html>Bad Gateway</html>', '"just a string"', '{"message":""}'] as $body) {
            $exception = ApiException::fromResponse(502, $body);

            $this->assertSame('The Vatly API responded with HTTP status 502.', $exception->getMessage(), $body);
            $this->assertSame($body, $exception->getResponseBody());
        }
    }

    /** @test */
    public function unexpectedKeysAndShapesDoNotBreakParsing(): void
    {
        $exception = ApiException::fromResponse(
            422,
            '{"message":"Invalid.","field":"email","details":"not an object","errors":{"email":"one message","quantity":[1,"Must be at least 1."]}}',
        );

        $this->assertSame('Invalid.', $exception->getMessage());
        $this->assertSame([], $exception->getDetails());
        $this->assertSame(['email' => ['one message'], 'quantity' => ['Must be at least 1.']], $exception->getErrors());
    }

    /** @test */
    public function aLegacyErrorObjectSuppliesTheMessage(): void
    {
        $this->assertSame('Legacy failure.', ApiException::fromResponse(400, '{"error":{"message":"Legacy failure."}}')->getMessage());
    }

    /** @test */
    public function exceptionsThrownWithoutAResponseHaveNoStatusOrBody(): void
    {
        $exception = new ApiException('Invalid resource id.');

        $this->assertNull($exception->getStatusCode());
        $this->assertNull($exception->getResponseBody());
        $this->assertNull($exception->getRetryAfter());
        $this->assertSame([], $exception->getDetails());
        $this->assertSame([], $exception->getErrors());
    }

    /** @test */
    public function apiAndWebhookSignatureExceptionsShareTheVatlyExceptionInterface(): void
    {
        $this->assertInstanceOf(VatlyException::class, ApiException::fromResponse(404, '{"message":"Checkout not found."}'));
        $this->assertInstanceOf(VatlyException::class, new InvalidSignatureException('Invalid webhook signature'));
        $this->assertInstanceOf(VatlyException::class, new WebhookSecretNotConfiguredException());
    }
}
