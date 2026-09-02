<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks\Events;

use DateTimeImmutable;
use DateTimeInterface;
use Vatly\API\Types\WebhookEventName;

/**
 * Event representing a subscription being canceled immediately at Vatly.
 *
 * The `object` carries `cancellationReason: merchant_request`, surfaced here as
 * {@see self::$cancellationReason}.
 *
 * @immutable
 */
class SubscriptionCanceledImmediately
{
    public const VATLY_EVENT_NAME = WebhookEventName::SUBSCRIPTION_CANCELED_IMMEDIATELY;

    public function __construct(
        public string $customerId,
        public string $subscriptionId,
        public DateTimeInterface $endsAt,
        public bool $testmode,
        /**
         * Why the subscription was canceled, read straight from the delivery, or
         * `null` if absent. Typically `merchant_request` for this event.
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
            endsAt: new DateTimeImmutable($webhook->createdAt),
            testmode: $webhook->testmode,
            cancellationReason: $webhook->object['cancellationReason'] ?? null,
        );
    }
}
