<?php

namespace Vatly\API\Types;

class WebhookEventName
{
    public const ORDER_PAID = 'order.paid';
    public const ORDER_CANCELED = 'order.canceled';
    public const ORDER_CHARGEBACK_RECEIVED = 'order.chargeback_received';
    public const ORDER_CHARGEBACK_REVERSED = 'order.chargeback_reversed';
    public const ORDER_PAYMENT_FAILED = 'order.payment_failed';
    public const REFUND_COMPLETED = 'refund.completed';
    public const REFUND_FAILED = 'refund.failed';
    public const REFUND_CANCELED = 'refund.canceled';
    public const SUBSCRIPTION_STARTED = 'subscription.started';
    public const SUBSCRIPTION_BILLING_UPDATED = 'subscription.billing_updated';
    public const SUBSCRIPTION_CANCELED_IMMEDIATELY = 'subscription.canceled_immediately';
    public const SUBSCRIPTION_CANCELED_FOR_NONPAYMENT = 'subscription.canceled_for_nonpayment';
    public const SUBSCRIPTION_CANCELED_WITH_GRACE_PERIOD = 'subscription.canceled_with_grace_period';
    public const SUBSCRIPTION_CANCELLATION_GRACE_PERIOD_COMPLETED = 'subscription.cancellation_grace_period_completed';
    public const SUBSCRIPTION_RESUMED = 'subscription.resumed';
    public const SUBSCRIPTION_UPDATED = 'subscription.updated';
    public const SUBSCRIPTION_UPDATE_SCHEDULED = 'subscription.update_scheduled';
    public const CHECKOUT_PAID = 'checkout.paid';
    public const CHECKOUT_FAILED = 'checkout.failed';
    public const CHECKOUT_CANCELED = 'checkout.canceled';
    public const CHECKOUT_EXPIRED = 'checkout.expired';

    public const ONE_OFF_PRODUCT_UPDATE_SUBMITTED = 'one_off_product.update_submitted';
    public const ONE_OFF_PRODUCT_UPDATE_APPROVED = 'one_off_product.update_approved';
    public const ONE_OFF_PRODUCT_UPDATE_REJECTED = 'one_off_product.update_rejected';
    public const ONE_OFF_PRODUCT_ARCHIVED = 'one_off_product.archived';
    public const ONE_OFF_PRODUCT_UNARCHIVED = 'one_off_product.unarchived';

    public const SUBSCRIPTION_PLAN_UPDATE_SUBMITTED = 'subscription_plan.update_submitted';
    public const SUBSCRIPTION_PLAN_UPDATE_APPROVED = 'subscription_plan.update_approved';
    public const SUBSCRIPTION_PLAN_UPDATE_REJECTED = 'subscription_plan.update_rejected';
    public const SUBSCRIPTION_PLAN_ARCHIVED = 'subscription_plan.archived';
    public const SUBSCRIPTION_PLAN_UNARCHIVED = 'subscription_plan.unarchived';

    /**
     * Verification call sent when a webhook endpoint is registered or its URL is
     * updated. Delivered as a normal webhook event with `entityType` `webhook`;
     * receivers can acknowledge it (2xx) without running event-specific handling.
     */
    public const WEBHOOK_SETUP = 'webhook.setup';
}
