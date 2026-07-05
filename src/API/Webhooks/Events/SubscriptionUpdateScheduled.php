<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks\Events;

use Vatly\API\Types\ScheduledSubscriptionUpdate;
use Vatly\API\Types\WebhookEventName;

/**
 * Event representing a subscription change **scheduled** for the next billing
 * cycle rather than applied immediately (the immediate case is
 * {@see SubscriptionUpdated}).
 *
 * On this delivery the webhook `object` is the subscription's *current* state,
 * and the target values it will become are carried in `object.scheduledUpdate`.
 * The event exposes those targets as a typed
 * {@see ScheduledSubscriptionUpdate}; it is built straight from the signed
 * webhook envelope with no follow-up API call.
 *
 * @immutable
 */
class SubscriptionUpdateScheduled
{
    public const VATLY_EVENT_NAME = WebhookEventName::SUBSCRIPTION_UPDATE_SCHEDULED;

    public function __construct(
        public string $customerId,
        public string $subscriptionId,
        public bool $testmode,
        /**
         * The plan/price/quantity/interval the subscription will switch to at
         * its next billing cycle.
         */
        public ScheduledSubscriptionUpdate $scheduledUpdate,
    ) {
        //
    }

    public static function fromWebhook(WebhookReceived $webhook): self
    {
        return new self(
            customerId: $webhook->object['customerId'],
            subscriptionId: $webhook->entityId,
            testmode: $webhook->testmode,
            scheduledUpdate: ScheduledSubscriptionUpdate::fromArray($webhook->object['scheduledUpdate']),
        );
    }
}
