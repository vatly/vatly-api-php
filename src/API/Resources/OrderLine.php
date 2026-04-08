<?php

namespace Vatly\API\Resources;

use Vatly\API\Resources\Links\OrderLineLinks;
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

    public Money $basePrice;

    public Money $total;


    public Money $subtotal;

    public TaxSummaryCollection $taxes;

    public OrderLineLinks $links;
}
