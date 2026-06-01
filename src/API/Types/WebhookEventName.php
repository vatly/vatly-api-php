<?php

namespace Vatly\API\Types;

class WebhookEventName
{
    public const ORDER_PAID = 'order.paid';
    public const ORDER_CANCELED = 'order.canceled';
    public const ORDER_CHARGEBACK_RECEIVED = 'order.chargeback_received';
    public const ORDER_CHARGEBACK_REVERSED = 'order.chargeback_reversed';
    public const PAYMENT_FAILED = 'payment.failed';
    public const REFUND_COMPLETED = 'refund.completed';
    public const REFUND_FAILED = 'refund.failed';
    public const REFUND_CANCELED = 'refund.canceled';
    public const SUBSCRIPTION_STARTED = 'subscription.started';
    public const SUBSCRIPTION_BILLING_UPDATED = 'subscription.billing_updated';
    public const SUBSCRIPTION_CANCELED_IMMEDIATELY = 'subscription.canceled_immediately';
    public const SUBSCRIPTION_CANCELED_WITH_GRACE_PERIOD = 'subscription.canceled_with_grace_period';
    public const SUBSCRIPTION_CANCELLATION_GRACE_PERIOD_COMPLETED = 'subscription.cancellation_grace_period_completed';
    public const SUBSCRIPTION_RESUMED = 'subscription.resumed';
    public const CHECKOUT_PAID = 'checkout.paid';
    public const CHECKOUT_FAILED = 'checkout.failed';
    public const CHECKOUT_CANCELED = 'checkout.canceled';
    public const CHECKOUT_EXPIRED = 'checkout.expired';

    /**
     * Verification call sent when a webhook endpoint is registered or its URL is
     * updated. Delivered as a normal webhook event with `entityType` `webhook`;
     * receivers can acknowledge it (2xx) without running event-specific handling.
     */
    public const WEBHOOK_SETUP = 'webhook.setup';
}
