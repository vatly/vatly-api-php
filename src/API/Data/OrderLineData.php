<?php

declare(strict_types=1);

namespace Vatly\API\Data;

use Vatly\API\Resources\OrderLine as ApiOrderLine;
use Vatly\API\Types\TaxSummaryCollection;

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
 * Money fields (`basePrice`, `total`, `subtotal`) are integer cents, derived
 * from the API line's decimal-string {@see \Vatly\API\Types\Money} via
 * {@see \Vatly\API\Types\Money::toCents()}.
 *
 * @immutable
 */
class OrderLineData
{
    public function __construct(
        public string $vatlyId,
        public string $description,
        public int $quantity,
        public int $basePrice,
        public int $total,
        public int $subtotal,
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
            basePrice: $line->basePrice->toCents(),
            total: $line->total->toCents(),
            subtotal: $line->subtotal->toCents(),
            taxSummary: $line->taxes,
            productType: $line->productType,
            productId: $line->productId,
        );
    }
}
