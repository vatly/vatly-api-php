<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks\Events;

use Vatly\API\Resources\SubscriptionPlan as ApiSubscriptionPlan;
use Vatly\API\Types\WebhookEventName;

/**
 * Event representing an archived subscription plan being **unarchived** — re-opened to new
 * business. `archivedAt` is now null.
 *
 * The signed webhook `object` is the subscription plan — byte-identical to the
 * `GET /v1/subscription-plans/{id}` body — so {@see \Vatly\API\Webhooks\WebhookEventFactory}
 * hydrates the api-php resource straight from the payload and builds the event
 * via {@see self::fromApiSubscriptionPlan()}, with no follow-up API call.
 *
 * @immutable
 */
class SubscriptionPlanUnarchived
{
    public const VATLY_EVENT_NAME = WebhookEventName::SUBSCRIPTION_PLAN_UNARCHIVED;

    public function __construct(
        public string $subscriptionPlanId,
        public bool $testmode,
        public ApiSubscriptionPlan $subscriptionPlan,
    ) {
        //
    }

    /**
     * Build from the api-php resource hydrated from the signed webhook payload.
     */
    public static function fromApiSubscriptionPlan(ApiSubscriptionPlan $subscriptionPlan): self
    {
        return new self(
            subscriptionPlanId: $subscriptionPlan->id,
            testmode: $subscriptionPlan->testmode,
            subscriptionPlan: $subscriptionPlan,
        );
    }
}
