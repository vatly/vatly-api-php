<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks\Events;

use Vatly\API\Types\CheckoutStatus;
use Vatly\API\Types\WebhookEventName;
use Vatly\API\Webhooks\Concerns\NormalizesWebhookMetadata;

/**
 * Event representing a hosted checkout canceled by the customer at Vatly.
 *
 * Built straight from the webhook payload: the `checkout.*` deliveries carry
 * the full Checkout resource (status, customerId, orderId, metadata) with no
 * sparse money/tax fields that would need a follow-up API GET — unlike orders
 * and subscriptions. This is a dispatched-only signal drivers consume for
 * receipts/analytics; no built-in reaction ships for it.
 *
 * @immutable
 */
class CheckoutCanceled
{
    use NormalizesWebhookMetadata;

    public const VATLY_EVENT_NAME = WebhookEventName::CHECKOUT_CANCELED;

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public string $checkoutId,
        public ?string $customerId,
        public ?string $orderId,
        public string $status,
        public bool $testmode,
        public ?array $metadata = null,
    ) {
        //
    }

    public static function fromWebhook(WebhookReceived $webhook): self
    {
        return new self(
            checkoutId: $webhook->entityId,
            customerId: $webhook->object['customerId'] ?? null,
            orderId: $webhook->object['orderId'] ?? null,
            status: $webhook->object['status'] ?? CheckoutStatus::STATUS_CANCELED,
            testmode: $webhook->testmode,
            metadata: self::normalizeMetadata($webhook->object['metadata'] ?? null),
        );
    }
}
