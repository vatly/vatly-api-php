<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks\Events;

use DateTimeImmutable;
use DateTimeInterface;
use Vatly\API\Types\WebhookEventName;

/**
 * Event representing a subscription being canceled with a grace period at Vatly.
 *
 * The `object` carries `cancellationReason` (`customer_request` when the
 * customer cancels from the portal, or `merchant_request`), surfaced here as
 * {@see self::$cancellationReason}.
 *
 * @immutable
 */
class SubscriptionCanceledWithGracePeriod
{
    public const VATLY_EVENT_NAME = WebhookEventName::SUBSCRIPTION_CANCELED_WITH_GRACE_PERIOD;

    public function __construct(
        public string $customerId,
        public string $subscriptionId,
        public DateTimeInterface $endsAt,
        public bool $testmode,
        /**
         * Why the subscription was canceled, read straight from the delivery, or
         * `null` if absent. Typically `customer_request` or `merchant_request`
         * for this event.
         *
         * @see \Vatly\API\Types\CancellationReason
         */
        public ?string $cancellationReason = null,
    ) {
        //
    }

    public static function fromWebhook(WebhookReceived $webhook): self
    {
        return new self(
            customerId: $webhook->object['customerId'],
            subscriptionId: $webhook->entityId,
            endsAt: new DateTimeImmutable($webhook->object['endedAt']),
            testmode: $webhook->testmode,
            cancellationReason: $webhook->object['cancellationReason'] ?? null,
        );
    }
}
