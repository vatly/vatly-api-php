<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks\Events;

use Vatly\API\Types\WebhookEventName;

/**
 * Event representing a `webhook.setup` verification call from Vatly.
 *
 * Vatly sends this signed delivery when a webhook endpoint is registered or its
 * URL changes, to confirm the endpoint is reachable. It uses the standard
 * webhook envelope (same signature, same `Vatly-Event-Id` header); `entityType`
 * is `webhook` and `object` is the (secret-free) endpoint config — which may be
 * empty or minimal. There is no enriched resource to fetch: consumers simply
 * acknowledge it with a `2xx` and take no event-specific action.
 *
 * Carries only the webhook envelope (mirroring {@see UnsupportedWebhookReceived}).
 *
 * @immutable
 */
class WebhookSetupReceived
{
    public const VATLY_EVENT_NAME = WebhookEventName::WEBHOOK_SETUP;

    /**
     * @param array<string, mixed> $object
     */
    public function __construct(
        public string $id,
        public string $resource,
        public string $eventName,
        public string $entityType,
        public string $entityId,
        public bool $testmode,
        public string $createdAt,
        public array $object,
    ) {
        //
    }

    public static function fromWebhook(WebhookReceived $webhook): self
    {
        return new self(
            id: $webhook->id,
            resource: $webhook->resource,
            eventName: $webhook->eventName,
            entityType: $webhook->entityType,
            entityId: $webhook->entityId,
            testmode: $webhook->testmode,
            createdAt: $webhook->createdAt,
            object: $webhook->object,
        );
    }
}
