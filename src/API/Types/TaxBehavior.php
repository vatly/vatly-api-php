<?php

namespace Vatly\API\Types;

/**
 * Whether a product's or plan's `basePrice` is tax-exclusive or tax-inclusive.
 *
 * Immutable after the product/plan is created. A checkout may not mix products
 * with different `taxBehavior` values.
 */
class TaxBehavior
{
    /**
     * `basePrice` is the net amount (B2B convention); tax is added on top at checkout.
     */
    public const EXCLUSIVE = "exclusive";

    /**
     * `basePrice` already includes tax (B2C convention); net is back-computed from
     * the buyer's jurisdiction.
     */
    public const INCLUSIVE = "inclusive";
}
