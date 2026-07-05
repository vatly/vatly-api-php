<?php

declare(strict_types=1);

namespace Vatly\API\Types;

/**
 * The target values for a subscription change scheduled to take effect at the
 * next billing cycle. Carried by the `subscription.update_scheduled` webhook in
 * `object.scheduledUpdate`; never returned by the REST API.
 *
 * @immutable
 */
class ScheduledSubscriptionUpdate
{
    public function __construct(
        public string $subscriptionPlanId,
        public string $name,
        public string $description,
        public Money $basePrice,
        public int $quantity,
        public string $interval,
        public int $intervalCount,
    ) {
        //
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            subscriptionPlanId: $data['subscriptionPlanId'],
            name: $data['name'],
            description: $data['description'],
            basePrice: Money::createResourceFromApiResult($data['basePrice']),
            quantity: (int) $data['quantity'],
            interval: $data['interval'],
            intervalCount: (int) $data['intervalCount'],
        );
    }
}
