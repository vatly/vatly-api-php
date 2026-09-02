<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks\Events;

use DateTimeImmutable;
use DateTimeInterface;
use Vatly\API\Types\CancellationReason;
use Vatly\API\Types\WebhookEventName;

/**
 * Event representing a subscription being canceled because payment recovery for
 * a failed renewal was exhausted (a hard cancellation, like
 * {@see SubscriptionCanceledImmediately}, but merchant-initiated vs.
 * nonpayment-initiated).
 *
 * The webhook `object` is the Subscription carrying
 * `cancellationReason: payment_failure`, surfaced here as {@see self::$cancellationReason}.
 *
 * @immutable
 */
class SubscriptionCanceledForNonpayment
{
    public const VATLY_EVENT_NAME = WebhookEventName::SUBSCRIPTION_CANCELED_FOR_NONPAYMENT;

    public function __construct(
        public string $customerId,
        public string $subscriptionId,
        public DateTimeInterface $endsAt,
        public bool $testmode,
        /**
         * Why the subscription was canceled — `payment_failure` for this event.
         *
         * @see CancellationReason
         */
        public string $cancellationReason = CancellationReason::PAYMENT_FAILURE,
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
            cancellationReason: $webhook->object['cancellationReason'] ?? CancellationReason::PAYMENT_FAILURE,
        );
    }
}
