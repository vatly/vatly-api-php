<?php

namespace Vatly\API\Resources;

use Vatly\API\Types\Money;
use Vatly\API\Types\TaxSummaryCollection;

class RefundLine extends BaseResource
{
    /**
     * @example refund_item_2a46f4c01d3b47979f4d7b3f58c98be7
     */
    public string $id;

    /**
     * @example refundline
     */
    public string $resource;

    /**
     * @example Refund for PDF Book
     */
    public string $description;

    public int $quantity;

    public Money $basePrice;

    public Money $total;

    public Money $subtotal;

    public TaxSummaryCollection $taxes;
}
