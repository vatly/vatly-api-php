<?php

namespace Vatly\API\Resources;

use Vatly\API\Types\Money;
use Vatly\API\Types\TaxSummaryCollection;

class OrderLine extends BaseResource
{
    /**
     * @example order_item_2a46f4c01d3b47979f4d7b3f58c98be7
     */
    public string $id;

    /**
     * @example orderline
     */
    public string $resource;

    /**
     * @example PDF Book
     */
    public string $description;

    public int $quantity;

    /**
     * The product type this line links to. Null until the backend ships it.
     *
     * @example subscription
     */
    public ?string $productType = null;

    /**
     * The linked product's id. For a subscription line this is the public
     * `subscription_…` id. Null until the backend ships it.
     *
     * @example subscription_2a46f4c01d3b47979f4d7b3f58c98be7
     */
    public ?string $productId = null;

    public Money $basePrice;

    public Money $total;

    public Money $subtotal;

    public TaxSummaryCollection $taxes;
}
