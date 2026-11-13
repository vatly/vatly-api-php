<?php

declare(strict_types=1);

namespace Vatly\API\Types;

use Vatly\API\Resources\OrderLine as ApiOrderLine;

/**
 * Data for a single order line from Vatly.
 *
 * Carries the per-line breakdown so drivers can persist first-class order
 * lines. `productType`/`productId` name the linked resource: an initial
 * signup line references the plan (`subscription_plan`), a renewal or
 * immediate-update line references the subscription (`subscription`).
 * To find the subscription a line belongs to, whichever kind of line it is,
 * read `relatedResources->subscriptionId`.
 *
 * `productType` is carried as the raw API string (no enum) so new backend
 * product types flow through without an api-php release.
 *
 * Money fields (`basePrice`, `total`, `subtotal`) are {@see Money} values
 * (decimal-string + currency). Consumers that need integer cents flatten at
 * their own persistence edge via {@see Money::toCents()}.
 *
 * @immutable
 */
class OrderLineData
{
    public function __construct(
        public string $vatlyId,
        public string $description,
        public int $quantity,
        public Money $basePrice,
        public Money $total,
        public Money $subtotal,
        public ?TaxSummaryCollection $taxSummary = null,
        public ?string $productType = null,
        public ?string $productId = null,
        /**
         * The line's `relatedResources`. Null on historical lines, on lines
         * unrelated to a subscription, and when the payload leaves the key
         * out. On an initial signup line its `subscriptionId` is null until
         * the subscription is available.
         */
        public ?OrderLineRelatedResources $relatedResources = null,
    ) {
    }

    public static function fromApiOrderLine(ApiOrderLine $line): self
    {
        return new self(
            vatlyId: $line->id,
            description: $line->description,
            quantity: $line->quantity,
            basePrice: $line->basePrice,
            total: $line->total,
            subtotal: $line->subtotal,
            taxSummary: $line->taxes,
            productType: $line->productType,
            productId: $line->productId,
            relatedResources: $line->relatedResources,
        );
    }
}
