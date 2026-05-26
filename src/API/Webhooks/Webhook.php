<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks;

use Vatly\API\Exceptions\InvalidSignatureException;

class Webhook
{
    /**
     * Parse and verify an incoming Vatly webhook.
     *
     * Verifies the HMAC-SHA256 signature, decodes the JSON payload, and returns
     * a typed {@see WebhookPayload} object ready for processing.
     *
     * @param  string  $payload    The raw request body (e.g. file_get_contents('php://input')).
     * @param  string  $signature  The value of the `Vatly-Signature` request header.
     * @param  string  $secret     Your webhook signing secret.
     *
     * @return WebhookPayload
     *
     * @throws InvalidSignatureException  When the signature does not match.
     * @throws \InvalidArgumentException  When the payload is not valid JSON or is missing required fields.
     */
    public static function parse(string $payload, string $signature, string $secret): WebhookPayload
    {
        (new WebhookSignatureValidator($secret))->verify($payload, $signature);

        $decoded = json_decode($payload);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(
                'Webhook payload is not valid JSON: '.json_last_error_msg()
            );
        }

        foreach (['id', 'resource', 'eventName', 'entityType', 'entityId', 'createdAt', 'testmode'] as $field) {
            if (! isset($decoded->{$field})) {
                throw new \InvalidArgumentException(
                    "Webhook payload is missing required field: {$field}"
                );
            }
        }

        $object = $decoded->object ?? null;
        if ($object !== null && ! is_object($object)) {
            throw new \InvalidArgumentException(
                'Webhook payload field "object" must be an object.'
            );
        }

        return new WebhookPayload(
            $decoded->id,
            $decoded->resource,
            $decoded->eventName,
            $decoded->entityType,
            $decoded->entityId,
            $decoded->createdAt,
            $decoded->testmode,
            $object,
        );
    }
}
