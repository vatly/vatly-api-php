<?php

namespace Vatly\API\Types;

class CheckoutStatus
{
    /**
     * The checkout has just been created and is awaiting payment.
     */
    public const STATUS_CREATED = "created";

    /**
     * The checkout has been paid successfully, order created.
     */
    public const STATUS_PAID = "paid";

    /**
     * The checkout has been canceled by the customer.
     */
    public const STATUS_CANCELED = "canceled";

    /**
     * The checkout payment failed.
     */
    public const STATUS_FAILED = "failed";

    /**
     * The checkout has expired without completion.
     */
    public const STATUS_EXPIRED = "expired";
}
