<?php

declare(strict_types=1);

namespace Vatly\API\Types;

use Vatly\API\Resources\OrderLine as ApiOrderLine;

/**
 * Data for a single order line from Vatly.
 *
 * Carries the per-line breakdown so drivers can persist first-class order
 * lines and traverse subscription↔orders via the generic
 * (`productType`, `productId`) pair. A line links to a subscription when
 * `productType === 'subscription'` → `productId` is the `subscription_…` id.
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
        );
    }
}
