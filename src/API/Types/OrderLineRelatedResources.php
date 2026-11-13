<?php

declare(strict_types=1);

namespace Vatly\API\Types;

/**
 * Per-line relationships on an order line (`lines[].relatedResources`).
 *
 * The line's `productType`/`productId` keep their meaning: an initial signup
 * line references the plan (`subscription_plan`), a renewal or immediate-update
 * line references the subscription. `subscriptionId` names the subscription
 * the line belongs to, whatever its `productType`.
 *
 * The line's `relatedResources` itself is `null` on historical lines and on
 * lines unrelated to a subscription (such as one-off product lines).
 *
 * @immutable
 */
class OrderLineRelatedResources
{
    public function __construct(
        /**
         * The subscription this line belongs to. `null` on an initial signup
         * line while the subscription is not yet available; fetch the order
         * again later to read it. A subscription id alone does not prove the
         * subscription is active: check its status before granting access.
         *
         * @example subscription_Hn5xWqVfKm8RjTgYbUcP
         */
        public ?string $subscriptionId = null,
    ) {
        //
    }

    /**
     * @param object|array<string, mixed> $value
     */
    public static function createResourceFromApiResult($value): self
    {
        $value = (array) $value;

        return new self($value['subscriptionId'] ?? null);
    }
}
