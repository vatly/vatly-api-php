<?php

namespace Vatly\API\Types;

class WebhookEvent
{
    public const ORDER_PAID = 'order.paid';
    public const ORDER_CANCELED = 'order.canceled';
    public const ORDER_CHARGEBACK_RECEIVED = 'order.chargeback_received';
    public const ORDER_CHARGEBACK_REVERSED = 'order.chargeback_reversed';
    public const REFUND_COMPLETED = 'refund.completed';
    public const REFUND_FAILED = 'refund.failed';
    public const REFUND_CANCELED = 'refund.canceled';
    public const SUBSCRIPTION_STARTED = 'subscription.started';
    public const SUBSCRIPTION_CANCELED_IMMEDIATELY = 'subscription.canceled_immediately';
    public const SUBSCRIPTION_CANCELED_WITH_GRACE_PERIOD = 'subscription.canceled_with_grace_period';
    public const SUBSCRIPTION_CANCELLATION_GRACE_PERIOD_COMPLETED = 'subscription.cancellation_grace_period_completed';
    public const CHECKOUT_EXPIRED = 'checkout.expired';
}
