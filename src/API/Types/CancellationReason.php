<?php

namespace Vatly\API\Types;

/**
 * Why a subscription or an order was canceled, or `null` unless it was. The
 * public values are intentionally decoupled from Vatly's internal domain reason
 * names. Orders only use {@see self::PAYMENT_FAILURE}.
 */
class CancellationReason
{
    /**
     * Payment recovery was exhausted: after failed renewals for a subscription,
     * or for this unpaid order.
     */
    public const PAYMENT_FAILURE = "payment_failure";

    /**
     * The merchant explicitly canceled the subscription.
     */
    public const MERCHANT_REQUEST = "merchant_request";

    /**
     * The customer canceled from the self-service portal.
     */
    public const CUSTOMER_REQUEST = "customer_request";
}
