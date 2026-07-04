<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks\Events;

use Vatly\API\Resources\Subscription as ApiSubscription;
use Vatly\API\Types\Money;
use Vatly\API\Types\WebhookEventName;

/**
 * Event representing a subscription that changed **immediately** at Vatly — a
 * plan, price, interval or quantity change applied right away (not deferred to
 * the next cycle; that is {@see SubscriptionUpdateScheduled}).
 *
 * The webhook `object` is the subscription carrying its new values, so the
 * factory hydrates the api-php resource straight from the signed payload and
 * builds the event from it via {@see self::fromApiSubscription()}; the
 * envelope-only {@see self::fromWebhook()} is a lossless fallback / test helper.
 *
 * @immutable
 */
class SubscriptionUpdated
{
    public const VATLY_EVENT_NAME = WebhookEventName::SUBSCRIPTION_UPDATED;

    public function __construct(
        public string $customerId,
        public string $subscriptionId,
        public string $planId,
        public string $name,
        public string $description,
        /**
         * Recurring price per billing cycle before taxes, after the change.
         */
        public Money $basePrice,
        public int $quantity,
        public string $interval,
        public int $intervalCount,
        public bool $testmode,
    ) {
        //
    }

    /**
     * Build from the api-php resource hydrated from the signed webhook payload.
     */
    public static function fromApiSubscription(ApiSubscription $subscription): self
    {
        return new self(
            customerId: $subscription->customerId ?? '',
            subscriptionId: $subscription->id,
            planId: $subscription->subscriptionPlanId,
            name: $subscription->name,
            description: $subscription->description,
            basePrice: $subscription->basePrice,
            quantity: $subscription->quantity,
            interval: $subscription->interval,
            intervalCount: $subscription->intervalCount,
            testmode: $subscription->testmode,
        );
    }

    /**
     * Webhook-payload-only build (factory fallback and test convenience).
     */
    public static function fromWebhook(WebhookReceived $webhook): self
    {
        return new self(
            customerId: $webhook->object['customerId'],
            subscriptionId: $webhook->entityId,
            planId: $webhook->object['subscriptionPlanId'],
            name: $webhook->object['name'],
            description: $webhook->object['description'],
            basePrice: Money::createResourceFromApiResult($webhook->object['basePrice']),
            quantity: (int) $webhook->object['quantity'],
            interval: $webhook->object['interval'],
            intervalCount: (int) $webhook->object['intervalCount'],
            testmode: $webhook->testmode,
        );
    }
}
