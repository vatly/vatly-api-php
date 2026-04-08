<?php

namespace Vatly\API\Types;

class ProductStatus
{
    /**
     * Product/plan is active and can be purchased.
     */
    public const ACTIVE = "active";

    /**
     * Product/plan is awaiting approval.
     */
    public const PENDING = "pending";

    /**
     * Product/plan has been rejected.
     */
    public const REJECTED = "rejected";
}
