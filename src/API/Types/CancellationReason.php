<?php

namespace Vatly\API\Types;

/**
 * Why a subscription was canceled, or `null` unless a cancellation has been
 * requested. The public values are intentionally decoupled from Vatly's
 * internal domain reason names.
 */
class CancellationReason
{
    /**
     * Payment recovery was exhausted after failed renewals.
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
