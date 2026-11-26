<?php

declare(strict_types=1);

namespace Vatly\API\Exceptions;

use Exception;

/**
 * Thrown when a call to the Vatly API fails, or is refused before it is sent.
 *
 * When the API answered with an error, {@see getStatusCode()} holds the HTTP status,
 * {@see getDetails()} and {@see getErrors()} the structured parts of the error body,
 * and {@see getResponseBody()} the raw body. Branch on the status code (or on the
 * subclass, such as {@see ValidationException}), not on the message text.
 *
 * The message never contains the request body or the API key.
 */
class ApiException extends Exception implements VatlyException
{
    private ?int $statusCode = null;

    /**
     * @var array<string, mixed>
     */
    private array $details = [];

    /**
     * @var array<string, string[]>
     */
    private array $errors = [];

    private ?string $responseBody = null;

    private ?int $retryAfter = null;

    /**
     * Build the exception for an error response from the Vatly API.
     *
     * Returns the subclass for the status: 401 {@see AuthenticationException},
     * 403 {@see ForbiddenException}, 404 {@see NotFoundException}, 409 {@see ConflictException},
     * 422 {@see ValidationException} and 429 {@see RateLimitException}. Other statuses
     * return an ApiException.
     *
     * @param array<string, string|string[]> $responseHeaders Header names in any case.
     */
    public static function fromResponse(int $statusCode, string $responseBody, array $responseHeaders = []): self
    {
        $body = json_decode($responseBody, true);
        $body = is_array($body) ? $body : [];

        $message = $body['message'] ?? $body['error']['message'] ?? null;

        if (! is_string($message) || trim($message) === '') {
            $message = "The Vatly API responded with HTTP status {$statusCode}.";
        }

        $exceptionClass = match ($statusCode) {
            401 => AuthenticationException::class,
            403 => ForbiddenException::class,
            404 => NotFoundException::class,
            409 => ConflictException::class,
            422 => ValidationException::class,
            429 => RateLimitException::class,
            default => self::class,
        };

        $exception = new $exceptionClass($message, $statusCode);
        $exception->statusCode = $statusCode;
        $exception->responseBody = $responseBody;
        $exception->details = is_array($body['details'] ?? null) ? $body['details'] : [];
        $exception->errors = self::parseErrors($body['errors'] ?? null);
        $exception->retryAfter = self::parseRetryAfter($responseHeaders);

        return $exception;
    }

    /**
     * The HTTP status of the API's error response, or `null` when the call failed
     * without a response (a connection error, or a check before sending).
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * The error body's `details` object, such as `['idempotency_outcome' => 'in_progress']`.
     * Empty when the response had none.
     *
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * The validation errors of a `422` response, keyed by field in dot notation,
     * such as `['products.0.id' => ['The selected products.0.id is invalid.']]`.
     * Empty for other responses.
     *
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * The raw body of the API's error response, or `null` when there was no response.
     */
    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    /**
     * Seconds to wait before retrying, from the response's `Retry-After` header.
     * Sent on `429` responses, and on a `409` while an idempotent request is in progress.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * @param mixed $errors
     * @return array<string, string[]>
     */
    private static function parseErrors($errors): array
    {
        if (! is_array($errors)) {
            return [];
        }

        $parsed = [];

        foreach ($errors as $field => $messages) {
            $parsed[(string) $field] = array_values(array_filter((array) $messages, 'is_string'));
        }

        return $parsed;
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    private static function parseRetryAfter(array $headers): ?int
    {
        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) !== 'retry-after') {
                continue;
            }

            $value = trim((string) (is_array($value) ? reset($value) : $value));

            if (ctype_digit($value)) {
                return (int) $value;
            }

            $timestamp = strtotime($value);

            return $timestamp === false ? null : max(0, $timestamp - time());
        }

        return null;
    }
}
