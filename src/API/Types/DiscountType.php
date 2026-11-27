<?php

namespace Vatly\API\Types;

/**
 * Known values of a {@see Discount}'s `discountType`. More values may be
 * added; treat an unknown value as a discount of an unknown kind.
 */
class DiscountType
{
    /**
     * A percentage off; `percentage` holds it.
     */
    public const PERCENTAGE = "percentage";

    /**
     * A fixed amount off; `percentage` is null.
     */
    public const FIXED_AMOUNT = "fixed_amount";
}
