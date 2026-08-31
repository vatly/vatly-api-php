<?php

declare(strict_types=1);

namespace Vatly\API\Types;

/**
 * The target values for a subscription change scheduled to take effect at the
 * next billing cycle.
 *
 * Present in two places, hydrated into this same typed class:
 * - on the REST `Subscription` resource as `$subscription->scheduledUpdate`
 *   (`null` when nothing is pending); and
 * - on the `subscription.update_scheduled` webhook in `object.scheduledUpdate`.
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
        /**
         * When the change takes effect — the subscription's next renewal date
         * (ISO 8601), or `null` if the subscription has no scheduled renewal.
         */
        public ?string $effectiveAt = null,
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
            effectiveAt: $data['effectiveAt'] ?? null,
        );
    }

    /**
     * Hydrate from an API/REST result (a `stdClass`, as the API client decodes a
     * response) or an array. Mirrors the other Types' `createResourceFromApiResult()`.
     *
     * @param object|array<string, mixed> $value
     */
    public static function createResourceFromApiResult($value): self
    {
        return self::fromArray((array) $value);
    }
}
