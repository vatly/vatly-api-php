<?php

namespace Vatly\API\Resources;

use Vatly\API\Types\Money;
use Vatly\API\Types\OrderLineRelatedResources;
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
     * The resource type this line links to. An initial subscription signup
     * line uses `subscription_plan`; renewal and immediate-update lines use
     * `subscription`. Null when the line has no product link.
     *
     * @example subscription_plan
     */
    public ?string $productType = null;

    /**
     * The linked resource's id: a plan id when `productType` is
     * `subscription_plan`, a subscription id when it is `subscription`.
     *
     * @example subscription_plan_2a46f4c01d3b47979f4d7b3f58c98be7
     */
    public ?string $productId = null;

    /**
     * Additional per-line relationships, such as the subscription this line
     * belongs to. Null on historical lines and on lines unrelated to a
     * subscription, and when an older response leaves the key out.
     */
    public ?OrderLineRelatedResources $relatedResources = null;

    public Money $basePrice;

    public Money $total;

    public Money $subtotal;

    public TaxSummaryCollection $taxes;
}
