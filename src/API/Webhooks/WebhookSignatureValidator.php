<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks;

use Vatly\API\Exceptions\InvalidSignatureException;

/**
 * Verify the `Vatly-Signature` header on incoming webhook deliveries.
 *
 * The header value has the structured form:
 *
 *     Vatly-Signature: t=<unix_seconds>,v1=<hex_hmac_sha256>
 *
 * where `v1 = hash_hmac('sha256', "{$t}.{$rawBody}", $webhookSecret)` and
 * `rawBody` is the **exact byte string** sent in the request body. Verifying
 * against parsed-then-reserialised JSON will not match — always pass the raw
 * request body (e.g. `file_get_contents('php://input')`).
 *
 * Vatly also emits `Vatly-Event-Id` on event deliveries. That value is stable
 * across retry attempts and is the right key for receiver-side deduplication.
 */
class WebhookSignatureValidator
{
    /**
     * Name of the HTTP header carrying the structured signature value.
     */
    public const SIGNATURE_HEADER_NAME = 'Vatly-Signature';

    /**
     * Name of the HTTP header carrying the event id. Stable across retries —
     * use it as the dedup key when handling deliveries.
     */
    public const EVENT_ID_HEADER_NAME = 'Vatly-Event-Id';

    /**
     * Signature scheme emitted in the header value (the `v1=<hex>` part).
     */
    public const SIGNATURE_SCHEME = 'v1';

    /**
     * Default replay-window tolerance, in seconds. Signatures whose `t`
     * differs from the current time by more than this are rejected.
     */
    public const DEFAULT_TOLERANCE_SECONDS = 300;

    protected string $webhookSecret;

    protected int $toleranceSeconds;

    public function __construct(string $webhookSecret, int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS)
    {
        $this->webhookSecret = $webhookSecret;
        $this->toleranceSeconds = $toleranceSeconds;
    }

    /**
     * Verify a signature header against the raw request body.
     *
     * @param  string  $payload          Raw request body bytes, exactly as received.
     * @param  string  $signatureHeader  Full value of the `Vatly-Signature` header.
     *
     * @throws InvalidSignatureException When the header is malformed, the
     *                                   timestamp is outside the tolerance
     *                                   window, or the HMAC does not match.
     */
    public function verify(string $payload, string $signatureHeader): void
    {
        $parsed = $this->parseHeader($signatureHeader);

        if ($parsed === null) {
            throw new InvalidSignatureException('Malformed webhook signature header');
        }

        [$timestamp, $signature] = $parsed;

        if (abs(time() - $timestamp) > $this->toleranceSeconds) {
            throw new InvalidSignatureException('Webhook signature timestamp outside tolerance window');
        }

        $expected = $this->calculateSignature($payload, $timestamp);

        if (! hash_equals($expected, $signature)) {
            throw new InvalidSignatureException('Invalid webhook signature');
        }
    }

    /**
     * Same as {@see verify()} but returns a bool instead of throwing.
     */
    public function isValid(string $payload, string $signatureHeader): bool
    {
        try {
            $this->verify($payload, $signatureHeader);
        } catch (InvalidSignatureException) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the expected `v1` HMAC for a (payload, timestamp) pair.
     *
     * Returns the hex digest only — not the full structured header value.
     */
    public function calculateSignature(string $payload, int $timestamp): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$payload, $this->webhookSecret);
    }

    /**
     * Parse `t=<unix>,v1=<hex>` into `[timestamp, signature]`, or null on
     * malformed input. Unknown scheme keys (e.g. a future `v2=`) are
     * tolerated so receivers that only understand `v1` keep working when
     * additional versions appear alongside it.
     *
     * @return array{0: int, 1: string}|null
     */
    private function parseHeader(string $header): ?array
    {
        if ($header === '') {
            return null;
        }

        $timestamp = null;
        $signature = null;

        foreach (explode(',', $header) as $part) {
            $kv = explode('=', $part, 2);

            if (count($kv) !== 2) {
                continue;
            }

            [$key, $value] = $kv;
            $key = trim($key);
            $value = trim($value);

            if ($key === 't' && ctype_digit($value)) {
                $timestamp = (int) $value;
            } elseif ($key === self::SIGNATURE_SCHEME && $value !== '') {
                $signature = $value;
            }
        }

        if ($timestamp === null || $signature === null) {
            return null;
        }

        return [$timestamp, $signature];
    }
}
