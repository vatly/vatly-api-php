<?php

declare(strict_types=1);

namespace Vatly\Tests\Webhooks;

use DateTimeInterface;
use Vatly\API\Resources\OneOffProduct as ApiOneOffProduct;
use Vatly\API\Resources\SubscriptionPlan as ApiSubscriptionPlan;
use Vatly\API\Webhooks\Events\CheckoutCanceled;
use Vatly\API\Webhooks\Events\CheckoutExpired;
use Vatly\API\Webhooks\Events\CheckoutFailed;
use Vatly\API\Webhooks\Events\CheckoutPaid;
use Vatly\API\Webhooks\Events\OneOffProductArchived;
use Vatly\API\Webhooks\Events\OneOffProductUnarchived;
use Vatly\API\Webhooks\Events\OneOffProductUpdateApproved;
use Vatly\API\Webhooks\Events\OneOffProductUpdateRejected;
use Vatly\API\Webhooks\Events\OneOffProductUpdateSubmitted;
use Vatly\API\Webhooks\Events\OrderCanceled;
use Vatly\API\Webhooks\Events\OrderChargebackReceived;
use Vatly\API\Webhooks\Events\OrderChargebackReversed;
use Vatly\API\Webhooks\Events\OrderPaid;
use Vatly\API\Webhooks\Events\OrderPaymentFailed;
use Vatly\API\Webhooks\Events\RefundCanceled;
use Vatly\API\Webhooks\Events\RefundCompleted;
use Vatly\API\Webhooks\Events\RefundFailed;
use Vatly\API\Webhooks\Events\SubscriptionBillingUpdated;
use Vatly\API\Webhooks\Events\SubscriptionCanceledForNonpayment;
use Vatly\API\Webhooks\Events\SubscriptionCanceledImmediately;
use Vatly\API\Webhooks\Events\SubscriptionCanceledWithGracePeriod;
use Vatly\API\Webhooks\Events\SubscriptionCancellationGracePeriodCompleted;
use Vatly\API\Webhooks\Events\SubscriptionPlanArchived;
use Vatly\API\Webhooks\Events\SubscriptionPlanUnarchived;
use Vatly\API\Webhooks\Events\SubscriptionPlanUpdateApproved;
use Vatly\API\Webhooks\Events\SubscriptionPlanUpdateRejected;
use Vatly\API\Webhooks\Events\SubscriptionPlanUpdateSubmitted;
use Vatly\API\Webhooks\Events\SubscriptionResumed;
use Vatly\API\Webhooks\Events\SubscriptionStarted;
use Vatly\API\Webhooks\Events\SubscriptionUpdated;
use Vatly\API\Webhooks\Events\SubscriptionUpdateScheduled;
use Vatly\API\Webhooks\Events\UnsupportedWebhookReceived;
use Vatly\API\Webhooks\Events\WebhookReceived;
use Vatly\API\Webhooks\Events\WebhookSetupReceived;
use Vatly\API\Webhooks\WebhookEventFactory;
use Vatly\API\Webhooks\WebhookPayload;
use Vatly\Tests\BaseTestCase;

/**
 * The webhook payload is the authoritative, HMAC-signed snapshot: its `object`
 * is byte-identical to the corresponding `GET /…/{id}` body. So the factory
 * builds every event straight from `$webhook->object` — money/tax-bearing
 * events by hydrating the api-php Resource, the rest from the envelope — and
 * never performs a follow-up API GET.
 *
 * The {@see VatlyApiClient} below is the spy-backed client from
 * {@see BaseTestCase}: it is only used to construct empty Resources for
 * in-memory hydration. No outgoing request is recorded by the spy, which is
 * exactly why these tests prove "zero API calls" by construction.
 */
class WebhookEventFactoryTest extends BaseTestCase
{
    private WebhookEventFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new WebhookEventFactory($this->client);
    }

    public function test_it_converts_upstream_webhook_payload_into_webhook_received_event(): void
    {
        $payload = new WebhookPayload(
            id: 'webhook_event_abc',
            resource: 'webhook_event',
            eventName: 'subscription.started',
            entityType: 'subscription',
            entityId: 'sub_123',
            testmode: false,
            createdAt: '2024-01-15T10:00:00Z',
            object: (object) ['customerId' => 'cus_456'],
        );

        $event = $this->factory->fromPayload($payload);

        $this->assertInstanceOf(WebhookReceived::class, $event);
        $this->assertSame('webhook_event_abc', $event->id);
        $this->assertSame('webhook_event', $event->resource);
        $this->assertSame('subscription.started', $event->eventName);
        $this->assertSame('subscription', $event->entityType);
        $this->assertSame('sub_123', $event->entityId);
        $this->assertFalse($event->testmode);
        $this->assertSame('2024-01-15T10:00:00Z', $event->createdAt);
        $this->assertSame('cus_456', $event->getCustomerId());
    }

    public function test_it_converts_payload_with_null_object_into_empty_array(): void
    {
        $payload = new WebhookPayload(
            id: 'webhook_event_abc',
            resource: 'webhook_event',
            eventName: 'unknown.event',
            entityType: 'unknown',
            entityId: 'res_123',
            testmode: false,
            createdAt: '2024-01-15T10:00:00Z',
            object: null,
        );

        $event = $this->factory->fromPayload($payload);

        $this->assertSame([], $event->object);
    }

    public function test_it_creates_subscription_started_event_from_fat_payload(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'subscription.started',
            entityType: 'subscription',
            entityId: 'sub_123',
            object: $this->fatSubscription([
                'id' => 'sub_123',
                'customerId' => 'cus_456',
                'subscriptionPlanId' => 'plan_789',
                'name' => 'Premium Plan',
                'quantity' => 1,
                'mandate' => ['method' => 'card', 'maskedIdentifier' => '4242'],
            ]),
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(SubscriptionStarted::class, $event);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('sub_123', $event->subscriptionId);
        $this->assertSame('plan_789', $event->planId);
        $this->assertSame('Premium Plan', $event->name);
        $this->assertSame(1, $event->quantity);
        $this->assertNotNull($event->mandate);
        $this->assertSame('card', $event->mandate->method);
        $this->assertSame('4242', $event->mandate->maskedIdentifier);
    }

    public function test_subscription_started_event_carries_null_mandate_when_payload_has_none(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'subscription.started',
            entityType: 'subscription',
            entityId: 'sub_no_mandate',
            object: $this->fatSubscription([
                'id' => 'sub_no_mandate',
                'customerId' => 'cus_456',
                'subscriptionPlanId' => 'plan_789',
                'name' => 'Premium Plan',
                'quantity' => 1,
                'mandate' => null,
            ]),
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(SubscriptionStarted::class, $event);
        $this->assertNull($event->mandate);
    }

    public function test_it_creates_subscription_billing_updated_event_from_fat_payload(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'subscription.billing_updated',
            entityType: 'subscription',
            entityId: 'sub_123',
            object: $this->fatSubscription([
                'id' => 'sub_123',
                'customerId' => 'cus_456',
                'subscriptionPlanId' => 'plan_789',
                'name' => 'Premium Plan',
                'quantity' => 1,
                'mandate' => ['method' => 'sepa_debit', 'maskedIdentifier' => 'NL91****4300'],
            ]),
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(SubscriptionBillingUpdated::class, $event);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('sub_123', $event->subscriptionId);
        $this->assertSame('plan_789', $event->planId);
        $this->assertSame('Premium Plan', $event->name);
        $this->assertSame(1, $event->quantity);
        $this->assertNotNull($event->mandate);
        $this->assertSame('sepa_debit', $event->mandate->method);
        $this->assertSame('NL91****4300', $event->mandate->maskedIdentifier);
    }

    public function test_it_creates_subscription_canceled_immediately_event_from_webhook(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'subscription.canceled_immediately',
            entityType: 'subscription',
            entityId: 'sub_123',
            object: [
                'customerId' => 'cus_456',
                'endedAt' => '2024-01-15T10:00:00Z',
            ],
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(SubscriptionCanceledImmediately::class, $event);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('sub_123', $event->subscriptionId);
    }

    public function test_it_creates_subscription_canceled_for_nonpayment_event_from_webhook(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'subscription.canceled_for_nonpayment',
            entityType: 'subscription',
            entityId: 'sub_123',
            object: [
                'customerId' => 'cus_456',
                'cancellationReason' => 'payment_failure',
            ],
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(SubscriptionCanceledForNonpayment::class, $event);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('sub_123', $event->subscriptionId);
        $this->assertSame('payment_failure', $event->cancellationReason);
    }

    public function test_it_creates_subscription_canceled_with_grace_period_event_from_webhook(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'subscription.canceled_with_grace_period',
            entityType: 'subscription',
            entityId: 'sub_123',
            object: [
                'customerId' => 'cus_456',
                'endedAt' => '2024-02-15T10:00:00Z',
            ],
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(SubscriptionCanceledWithGracePeriod::class, $event);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('sub_123', $event->subscriptionId);
        $this->assertInstanceOf(DateTimeInterface::class, $event->endsAt);
    }

    public function test_it_creates_subscription_resumed_event_from_webhook(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'subscription.resumed',
            entityType: 'subscription',
            entityId: 'sub_123',
            object: ['customerId' => 'cus_456'],
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(SubscriptionResumed::class, $event);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('sub_123', $event->subscriptionId);
    }

    public function test_it_creates_subscription_updated_event_from_fat_payload(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'subscription.updated',
            entityType: 'subscription',
            entityId: 'sub_123',
            object: $this->fatSubscription([
                'id' => 'sub_123',
                'customerId' => 'cus_456',
                'subscriptionPlanId' => 'plan_new',
                'name' => 'Pro Plan',
                'quantity' => 5,
                'basePrice' => ['value' => '99.99', 'currency' => 'EUR'],
                'interval' => 'month',
                'mandate' => null,
            ]),
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(SubscriptionUpdated::class, $event);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('sub_123', $event->subscriptionId);
        $this->assertSame('plan_new', $event->planId);
        $this->assertSame('Pro Plan', $event->name);
        $this->assertSame(9999, $event->basePrice->toCents());
        $this->assertSame('EUR', $event->basePrice->currency);
        $this->assertSame(5, $event->quantity);
        $this->assertSame('month', $event->interval);
        $this->assertSame(1, $event->intervalCount);
    }

    public function test_it_creates_subscription_update_scheduled_event_from_webhook(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'subscription.update_scheduled',
            entityType: 'subscription',
            entityId: 'sub_123',
            object: [
                'customerId' => 'cus_456',
                'scheduledUpdate' => [
                    'subscriptionPlanId' => 'plan_next',
                    'name' => 'Pro Annual',
                    'description' => 'Billed yearly',
                    'basePrice' => ['value' => '990.00', 'currency' => 'EUR'],
                    'quantity' => 2,
                    'interval' => 'year',
                    'intervalCount' => 1,
                    'effectiveAt' => '2024-03-15T10:30:00Z',
                ],
            ],
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(SubscriptionUpdateScheduled::class, $event);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('sub_123', $event->subscriptionId);
        $this->assertSame('plan_next', $event->scheduledUpdate->subscriptionPlanId);
        $this->assertSame('Pro Annual', $event->scheduledUpdate->name);
        $this->assertSame(99000, $event->scheduledUpdate->basePrice->toCents());
        $this->assertSame(2, $event->scheduledUpdate->quantity);
        $this->assertSame('year', $event->scheduledUpdate->interval);
        $this->assertSame('2024-03-15T10:30:00Z', $event->scheduledUpdate->effectiveAt);
    }

    public function test_it_creates_order_paid_event_from_fat_payload_with_tax_breakdown(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'order.paid',
            entityType: 'order',
            entityId: 'ord_123',
            object: $this->fatOrder([
                'id' => 'ord_123',
                'customerId' => 'cus_456',
                'status' => 'paid',
                'total' => ['currency' => 'EUR', 'value' => '99.00'],
                'subtotal' => ['currency' => 'EUR', 'value' => '81.82'],
                'taxRates' => [
                    ['name' => 'VAT', 'percentage' => 21.0, 'taxablePercentage' => 100.0, 'amount' => '17.18'],
                ],
                'invoiceNumber' => 'INV-2024-001',
                'paymentMethod' => 'credit_card',
            ]),
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(OrderPaid::class, $event);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('ord_123', $event->orderId);
        $this->assertSame('paid', $event->status);
        $this->assertSame(9900, $event->total->toCents());
        $this->assertSame(8182, $event->subtotal->toCents());
        $this->assertSame('EUR', $event->total->currency);
        $this->assertSame('INV-2024-001', $event->invoiceNumber);
        $this->assertSame('credit_card', $event->paymentMethod);
        $this->assertCount(1, $event->taxSummary->items);
        $this->assertSame('VAT', $event->taxSummary->items[0]->taxRate->name);
        $this->assertSame(21.0, $event->taxSummary->items[0]->taxRate->percentage);
        $this->assertSame(1718, $event->taxSummary->items[0]->amount->toCents());
        $this->assertSame('EUR', $event->taxSummary->items[0]->amount->currency);
    }

    public function test_order_paid_event_carries_mapped_order_lines_from_the_fat_payload(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'order.paid',
            entityType: 'order',
            entityId: 'ord_123',
            object: $this->fatOrder([
                'id' => 'ord_123',
                'customerId' => 'cus_456',
                'status' => 'paid',
                'total' => ['currency' => 'EUR', 'value' => '99.00'],
                'subtotal' => ['currency' => 'EUR', 'value' => '81.82'],
                'taxRates' => [
                    ['name' => 'VAT', 'percentage' => 21.0, 'taxablePercentage' => 100.0, 'amount' => '17.18'],
                ],
                'invoiceNumber' => 'INV-2024-001',
                'paymentMethod' => 'credit_card',
                'lines' => [
                    [
                        'id' => 'order_item_sub',
                        'description' => 'Pro plan',
                        'quantity' => 1,
                        'basePrice' => '20.00',
                        'total' => '24.20',
                        'subtotal' => '20.00',
                        'productType' => 'subscription',
                        'productId' => 'subscription_abc',
                    ],
                    [
                        'id' => 'order_item_legacy',
                        'description' => 'Unattributed line',
                        'quantity' => 2,
                        'basePrice' => '5.00',
                        'total' => '12.10',
                        'subtotal' => '10.00',
                    ],
                ],
            ]),
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(OrderPaid::class, $event);
        $this->assertCount(2, $event->lines);

        $this->assertSame('order_item_sub', $event->lines[0]->vatlyId);
        $this->assertSame(2000, $event->lines[0]->basePrice->toCents());
        $this->assertSame(2420, $event->lines[0]->total->toCents());
        $this->assertSame(2000, $event->lines[0]->subtotal->toCents());
        $this->assertSame('subscription', $event->lines[0]->productType);
        $this->assertSame('subscription_abc', $event->lines[0]->productId);

        $this->assertSame('order_item_legacy', $event->lines[1]->vatlyId);
        $this->assertSame(2, $event->lines[1]->quantity);
        $this->assertNull($event->lines[1]->productType);
        $this->assertNull($event->lines[1]->productId);
    }

    public function test_it_creates_payment_failed_event_from_fat_payload(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'order.payment_failed',
            entityType: 'order',
            entityId: 'ord_dunning_1',
            object: $this->fatOrder([
                'id' => 'ord_dunning_1',
                'customerId' => 'cus_456',
                'status' => 'pending',
                'total' => ['currency' => 'EUR', 'value' => '49.00'],
                'subtotal' => ['currency' => 'EUR', 'value' => '40.50'],
                'taxRates' => [
                    ['name' => 'VAT', 'percentage' => 21.0, 'taxablePercentage' => 100.0, 'amount' => '8.50'],
                ],
                'invoiceNumber' => null,
                'paymentMethod' => 'sepa_direct_debit',
            ]),
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(OrderPaymentFailed::class, $event);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('ord_dunning_1', $event->orderId);
        $this->assertSame('pending', $event->status);
        $this->assertSame(4900, $event->total->toCents());
        $this->assertSame(4050, $event->subtotal->toCents());
        $this->assertSame('EUR', $event->total->currency);
        $this->assertNull($event->invoiceNumber);
        $this->assertSame('sepa_direct_debit', $event->paymentMethod);
        $this->assertCount(1, $event->taxSummary->items);
        $this->assertSame('VAT', $event->taxSummary->items[0]->taxRate->name);
        $this->assertSame(850, $event->taxSummary->items[0]->amount->toCents());
    }

    public function test_it_creates_order_canceled_event_from_webhook(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'order.canceled',
            entityType: 'order',
            entityId: 'ord_123',
            object: [
                'customerId' => 'cus_456',
                'status' => 'canceled',
            ],
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(OrderCanceled::class, $event);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('ord_123', $event->orderId);
        $this->assertSame('canceled', $event->status);
    }

    public function test_it_creates_order_chargeback_received_event_from_fat_payload(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'order.chargeback_received',
            entityType: 'order',
            entityId: 'ord_123',
            object: $this->fatChargeback([
                'id' => 'chargeback_789',
                'customerId' => 'cus_456',
                'status' => 'received',
                'originalOrderId' => 'ord_original_1',
                'reason' => 'fraudulent',
                'total' => ['currency' => 'EUR', 'value' => '99.00'],
                'subtotal' => ['currency' => 'EUR', 'value' => '81.82'],
                'taxRates' => [
                    ['name' => 'VAT', 'percentage' => 21.0, 'taxablePercentage' => 100.0, 'amount' => '17.18'],
                ],
            ]),
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(OrderChargebackReceived::class, $event);
        $this->assertSame('ord_original_1', $event->orderId);
        $this->assertSame('chargeback_789', $event->chargebackId);
        $this->assertSame('ord_original_1', $event->originalOrderId);
        $this->assertSame('fraudulent', $event->reason);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('received', $event->status);
        $this->assertNotNull($event->total);
        $this->assertSame(9900, $event->total->toCents());
        $this->assertSame('EUR', $event->currency);
        $this->assertNotNull($event->taxSummary);
        $this->assertCount(1, $event->taxSummary->items);
        $this->assertSame(1718, $event->taxSummary->items[0]->amount->toCents());
    }

    public function test_it_creates_order_chargeback_reversed_event_from_fat_payload(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'order.chargeback_reversed',
            entityType: 'order',
            entityId: 'ord_123',
            object: $this->fatChargeback([
                'id' => 'chargeback_789',
                'customerId' => 'cus_456',
                'status' => 'reversed',
                'originalOrderId' => 'ord_original_1',
                'reason' => '',
                'total' => ['currency' => 'EUR', 'value' => '99.00'],
                'subtotal' => ['currency' => 'EUR', 'value' => '81.82'],
                'taxRates' => [],
            ]),
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(OrderChargebackReversed::class, $event);
        $this->assertSame('ord_original_1', $event->orderId);
        $this->assertSame('chargeback_789', $event->chargebackId);
        $this->assertSame('reversed', $event->status);
        // An empty `reason` string maps to null per the event's contract.
        $this->assertNull($event->reason);
    }

    public function test_it_creates_refund_completed_event_from_fat_payload(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'refund.completed',
            entityType: 'refund',
            entityId: 'refund_123',
            object: $this->fatRefund([
                'id' => 'refund_123',
                'customerId' => 'cus_456',
                'status' => 'refunded',
                'originalOrderId' => 'ord_original_1',
                'total' => ['currency' => 'EUR', 'value' => '99.00'],
                'subtotal' => ['currency' => 'EUR', 'value' => '81.82'],
                'taxRates' => [
                    ['name' => 'VAT', 'percentage' => 21.0, 'taxablePercentage' => 100.0, 'amount' => '17.18'],
                ],
            ]),
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(RefundCompleted::class, $event);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('refund_123', $event->refundId);
        $this->assertSame('refunded', $event->status);
        $this->assertSame('ord_original_1', $event->originalOrderId);
        $this->assertSame(9900, $event->total->toCents());
        $this->assertSame(8182, $event->subtotal->toCents());
        $this->assertSame('EUR', $event->total->currency);
        $this->assertCount(1, $event->taxSummary->items);
        $this->assertSame('VAT', $event->taxSummary->items[0]->taxRate->name);
        $this->assertSame(1718, $event->taxSummary->items[0]->amount->toCents());
    }

    public function test_it_creates_refund_failed_event_from_fat_payload(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'refund.failed',
            entityType: 'refund',
            entityId: 'refund_failed_1',
            object: $this->fatRefund([
                'id' => 'refund_failed_1',
                'customerId' => 'cus_456',
                'status' => 'failed',
                'originalOrderId' => 'ord_original_1',
                'total' => ['currency' => 'EUR', 'value' => '49.00'],
                'subtotal' => ['currency' => 'EUR', 'value' => '40.50'],
                'taxRates' => [],
            ]),
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(RefundFailed::class, $event);
        $this->assertSame('refund_failed_1', $event->refundId);
        $this->assertSame('failed', $event->status);
        $this->assertSame(4900, $event->total->toCents());
    }

    public function test_it_creates_refund_canceled_event_from_fat_payload(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'refund.canceled',
            entityType: 'refund',
            entityId: 'refund_canceled_1',
            object: $this->fatRefund([
                'id' => 'refund_canceled_1',
                'customerId' => 'cus_456',
                'status' => 'canceled',
                'originalOrderId' => 'ord_original_1',
                'total' => ['currency' => 'EUR', 'value' => '10.00'],
                'subtotal' => ['currency' => 'EUR', 'value' => '8.26'],
                'taxRates' => [],
            ]),
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(RefundCanceled::class, $event);
        $this->assertSame('refund_canceled_1', $event->refundId);
        $this->assertSame('canceled', $event->status);
    }

    public function test_it_creates_checkout_paid_event_from_webhook(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'checkout.paid',
            entityType: 'checkout',
            entityId: 'checkout_123',
            object: [
                'customerId' => 'cus_456',
                'orderId' => 'ord_789',
                'status' => 'paid',
                'metadata' => ['cart_id' => 'cart_1'],
            ],
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(CheckoutPaid::class, $event);
        $this->assertSame('checkout_123', $event->checkoutId);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('ord_789', $event->orderId);
        $this->assertSame('paid', $event->status);
        $this->assertSame(['cart_id' => 'cart_1'], $event->metadata);
    }

    public function test_it_creates_checkout_failed_event_from_webhook(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'checkout.failed',
            entityType: 'checkout',
            entityId: 'checkout_123',
            object: ['customerId' => 'cus_456', 'status' => 'failed'],
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(CheckoutFailed::class, $event);
        $this->assertSame('checkout_123', $event->checkoutId);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertNull($event->orderId);
        $this->assertSame('failed', $event->status);
    }

    public function test_it_creates_checkout_canceled_event_from_webhook(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'checkout.canceled',
            entityType: 'checkout',
            entityId: 'checkout_123',
            object: ['customerId' => 'cus_456'],
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(CheckoutCanceled::class, $event);
        $this->assertSame('checkout_123', $event->checkoutId);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('canceled', $event->status);
    }

    public function test_it_creates_checkout_expired_event_from_webhook(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'checkout.expired',
            entityType: 'checkout',
            entityId: 'checkout_123',
            object: [],
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(CheckoutExpired::class, $event);
        $this->assertSame('checkout_123', $event->checkoutId);
        $this->assertNull($event->customerId);
        $this->assertSame('expired', $event->status);
    }

    public function test_it_creates_subscription_cancellation_grace_period_completed_event_from_webhook(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'subscription.cancellation_grace_period_completed',
            entityType: 'subscription',
            entityId: 'sub_123',
            object: [
                'customerId' => 'cus_456',
                'endedAt' => '2024-02-15T10:00:00Z',
            ],
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(SubscriptionCancellationGracePeriodCompleted::class, $event);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('sub_123', $event->subscriptionId);
        $this->assertInstanceOf(DateTimeInterface::class, $event->endsAt);
        $this->assertSame('2024-02-15T10:00:00+00:00', $event->endsAt->format('c'));
    }

    public function test_it_creates_unsupported_webhook_received_for_unknown_events(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'unknown.event',
            entityType: 'unknown',
            entityId: 'res_123',
            object: [],
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(UnsupportedWebhookReceived::class, $event);
        $this->assertSame('unknown.event', $event->eventName);
    }

    public function test_it_creates_webhook_setup_received_event_from_webhook(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'webhook.setup',
            entityType: 'webhook',
            entityId: 'wh_123',
            object: ['url' => 'https://example.test/webhooks/vatly'],
        );

        $event = $this->factory->createFromWebhook($webhook);

        // Regression: `webhook.setup` used to fall through to Unsupported.
        $this->assertInstanceOf(WebhookSetupReceived::class, $event);
        $this->assertSame('webhook.setup', $event->eventName);
        $this->assertSame('wh_123', $event->entityId);
        $this->assertSame(['url' => 'https://example.test/webhooks/vatly'], $event->object);
    }

    public function test_it_creates_one_off_product_update_submitted_event_from_fat_payload(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'one_off_product.update_submitted',
            entityType: 'one_off_product',
            entityId: 'one_off_product_123',
            object: [
                'id' => 'one_off_product_123',
                'resource' => 'one_off_product',
                'testmode' => false,
                'name' => 'Premium License',
                'description' => 'Lifetime access',
                'basePrice' => ['value' => '299.00', 'currency' => 'EUR'],
                'taxBehavior' => 'exclusive',
                'productType' => 'saas',
                'status' => 'active',
                'archivedAt' => null,
                'pendingUpdates' => ['basePrice' => ['value' => '349.00', 'currency' => 'EUR']],
                'updateStatus' => 'pending',
                'createdAt' => '2024-01-15T10:30:00Z',
                'links' => ['self' => ['href' => 'https://api.vatly.com/v1/one-off-products/one_off_product_123', 'type' => 'application/json']],
            ],
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(OneOffProductUpdateSubmitted::class, $event);
        $this->assertSame('one_off_product_123', $event->oneOffProductId);
        $this->assertFalse($event->testmode);
        $this->assertInstanceOf(ApiOneOffProduct::class, $event->oneOffProduct);
        $this->assertSame('Premium License', $event->oneOffProduct->name);
        $this->assertSame('exclusive', $event->oneOffProduct->taxBehavior);
        $this->assertSame('299.00', $event->oneOffProduct->basePrice->value);
        $this->assertSame('pending', $event->oneOffProduct->updateStatus);
        $this->assertSame('349.00', $event->oneOffProduct->pendingUpdates->basePrice->value);
        // Spy records nothing: hydration is in-memory, no follow-up API GET.
        $this->assertSame(0, $this->httpClient->countRecordedSends());
    }

    public function test_it_creates_one_off_product_archived_event_from_fat_payload(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'one_off_product.archived',
            entityType: 'one_off_product',
            entityId: 'one_off_product_123',
            object: [
                'id' => 'one_off_product_123',
                'resource' => 'one_off_product',
                'testmode' => true,
                'name' => 'Premium License',
                'description' => 'Lifetime access',
                'basePrice' => ['value' => '299.00', 'currency' => 'EUR'],
                'taxBehavior' => 'exclusive',
                'productType' => 'saas',
                'status' => 'active',
                'archivedAt' => '2024-02-01T00:00:00Z',
                'createdAt' => '2024-01-15T10:30:00Z',
                'links' => ['self' => ['href' => 'https://api.vatly.com/v1/one-off-products/one_off_product_123', 'type' => 'application/json']],
            ],
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(OneOffProductArchived::class, $event);
        $this->assertTrue($event->testmode);
        $this->assertTrue($event->oneOffProduct->isArchived());
        $this->assertSame('2024-02-01T00:00:00Z', $event->oneOffProduct->archivedAt);
    }

    public function test_it_creates_subscription_plan_update_approved_event_from_fat_payload(): void
    {
        $webhook = $this->makeWebhook(
            eventName: 'subscription_plan.update_approved',
            entityType: 'subscription_plan',
            entityId: 'subscription_plan_123',
            object: [
                'id' => 'subscription_plan_123',
                'resource' => 'subscription_plan',
                'testmode' => false,
                'name' => 'Pro Weekly',
                'description' => 'Billed weekly',
                'basePrice' => ['value' => '9.00', 'currency' => 'EUR'],
                'taxBehavior' => 'inclusive',
                'interval' => 'week',
                'intervalCount' => 1,
                'productType' => 'saas',
                'status' => 'active',
                'archivedAt' => null,
                'pendingUpdates' => null,
                'updateStatus' => null,
                'createdAt' => '2024-01-15T10:30:00Z',
                'links' => ['self' => ['href' => 'https://api.vatly.com/v1/subscription-plans/subscription_plan_123', 'type' => 'application/json']],
            ],
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf(SubscriptionPlanUpdateApproved::class, $event);
        $this->assertSame('subscription_plan_123', $event->subscriptionPlanId);
        $this->assertFalse($event->testmode);
        $this->assertInstanceOf(ApiSubscriptionPlan::class, $event->subscriptionPlan);
        $this->assertSame('Pro Weekly', $event->subscriptionPlan->name);
        $this->assertSame('week', $event->subscriptionPlan->interval);
        $this->assertSame('inclusive', $event->subscriptionPlan->taxBehavior);
        $this->assertSame('9.00', $event->subscriptionPlan->basePrice->value);
        $this->assertNull($event->subscriptionPlan->updateStatus);
        $this->assertSame(0, $this->httpClient->countRecordedSends());
    }

    /**
     * @dataProvider catalogueEventTypeProvider
     */
    public function test_catalogue_events_resolve_to_their_typed_dto(string $eventName, string $expectedClass, string $entityType, string $entityId): void
    {
        $webhook = $this->makeWebhook(
            eventName: $eventName,
            entityType: $entityType,
            entityId: $entityId,
            object: [
                'id' => $entityId,
                'resource' => $entityType,
                'testmode' => true,
                'name' => 'Name',
                'description' => 'Description',
                'basePrice' => ['value' => '10.00', 'currency' => 'EUR'],
                'taxBehavior' => 'exclusive',
                'interval' => 'month',
                'intervalCount' => 1,
                'productType' => 'saas',
                'status' => 'active',
                'archivedAt' => null,
                'createdAt' => '2024-01-15T10:30:00Z',
                'links' => ['self' => ['href' => 'https://api.vatly.com/v1/x/'.$entityId, 'type' => 'application/json']],
            ],
        );

        $event = $this->factory->createFromWebhook($webhook);

        $this->assertInstanceOf($expectedClass, $event);
    }

    /**
     * @return array<string, array{string, class-string, string, string}>
     */
    public static function catalogueEventTypeProvider(): array
    {
        return [
            'one_off_product.update_submitted' => ['one_off_product.update_submitted', OneOffProductUpdateSubmitted::class, 'one_off_product', 'one_off_product_1'],
            'one_off_product.update_approved' => ['one_off_product.update_approved', OneOffProductUpdateApproved::class, 'one_off_product', 'one_off_product_1'],
            'one_off_product.update_rejected' => ['one_off_product.update_rejected', OneOffProductUpdateRejected::class, 'one_off_product', 'one_off_product_1'],
            'one_off_product.archived' => ['one_off_product.archived', OneOffProductArchived::class, 'one_off_product', 'one_off_product_1'],
            'one_off_product.unarchived' => ['one_off_product.unarchived', OneOffProductUnarchived::class, 'one_off_product', 'one_off_product_1'],
            'subscription_plan.update_submitted' => ['subscription_plan.update_submitted', SubscriptionPlanUpdateSubmitted::class, 'subscription_plan', 'subscription_plan_1'],
            'subscription_plan.update_approved' => ['subscription_plan.update_approved', SubscriptionPlanUpdateApproved::class, 'subscription_plan', 'subscription_plan_1'],
            'subscription_plan.update_rejected' => ['subscription_plan.update_rejected', SubscriptionPlanUpdateRejected::class, 'subscription_plan', 'subscription_plan_1'],
            'subscription_plan.archived' => ['subscription_plan.archived', SubscriptionPlanArchived::class, 'subscription_plan', 'subscription_plan_1'],
            'subscription_plan.unarchived' => ['subscription_plan.unarchived', SubscriptionPlanUnarchived::class, 'subscription_plan', 'subscription_plan_1'],
        ];
    }

    public function test_catalogue_event_name_constants_match_the_spec_strings(): void
    {
        $this->assertSame('one_off_product.update_submitted', \Vatly\API\Types\WebhookEventName::ONE_OFF_PRODUCT_UPDATE_SUBMITTED);
        $this->assertSame('one_off_product.update_approved', \Vatly\API\Types\WebhookEventName::ONE_OFF_PRODUCT_UPDATE_APPROVED);
        $this->assertSame('one_off_product.update_rejected', \Vatly\API\Types\WebhookEventName::ONE_OFF_PRODUCT_UPDATE_REJECTED);
        $this->assertSame('one_off_product.archived', \Vatly\API\Types\WebhookEventName::ONE_OFF_PRODUCT_ARCHIVED);
        $this->assertSame('one_off_product.unarchived', \Vatly\API\Types\WebhookEventName::ONE_OFF_PRODUCT_UNARCHIVED);
        $this->assertSame('subscription_plan.update_submitted', \Vatly\API\Types\WebhookEventName::SUBSCRIPTION_PLAN_UPDATE_SUBMITTED);
        $this->assertSame('subscription_plan.update_approved', \Vatly\API\Types\WebhookEventName::SUBSCRIPTION_PLAN_UPDATE_APPROVED);
        $this->assertSame('subscription_plan.update_rejected', \Vatly\API\Types\WebhookEventName::SUBSCRIPTION_PLAN_UPDATE_REJECTED);
        $this->assertSame('subscription_plan.archived', \Vatly\API\Types\WebhookEventName::SUBSCRIPTION_PLAN_ARCHIVED);
        $this->assertSame('subscription_plan.unarchived', \Vatly\API\Types\WebhookEventName::SUBSCRIPTION_PLAN_UNARCHIVED);
    }

    public function test_it_returns_list_of_supported_events(): void
    {
        $supported = $this->factory->getSupportedEvents();

        $this->assertContains('subscription.started', $supported);
        $this->assertContains('subscription.billing_updated', $supported);
        $this->assertContains('subscription.resumed', $supported);
        $this->assertContains('subscription.updated', $supported);
        $this->assertContains('subscription.update_scheduled', $supported);
        $this->assertContains('subscription.canceled_immediately', $supported);
        $this->assertContains('subscription.canceled_for_nonpayment', $supported);
        $this->assertContains('subscription.canceled_with_grace_period', $supported);
        $this->assertContains('subscription.cancellation_grace_period_completed', $supported);
        $this->assertContains('order.paid', $supported);
        $this->assertContains('order.canceled', $supported);
        $this->assertContains('order.chargeback_received', $supported);
        $this->assertContains('order.chargeback_reversed', $supported);
        $this->assertContains('order.payment_failed', $supported);
        $this->assertContains('checkout.paid', $supported);
        $this->assertContains('checkout.failed', $supported);
        $this->assertContains('checkout.canceled', $supported);
        $this->assertContains('checkout.expired', $supported);
        $this->assertContains('refund.completed', $supported);
        $this->assertContains('refund.failed', $supported);
        $this->assertContains('refund.canceled', $supported);
        $this->assertContains('one_off_product.update_submitted', $supported);
        $this->assertContains('one_off_product.update_approved', $supported);
        $this->assertContains('one_off_product.update_rejected', $supported);
        $this->assertContains('one_off_product.archived', $supported);
        $this->assertContains('one_off_product.unarchived', $supported);
        $this->assertContains('subscription_plan.update_submitted', $supported);
        $this->assertContains('subscription_plan.update_approved', $supported);
        $this->assertContains('subscription_plan.update_rejected', $supported);
        $this->assertContains('subscription_plan.archived', $supported);
        $this->assertContains('subscription_plan.unarchived', $supported);
        $this->assertContains('webhook.setup', $supported);
    }

    public function test_it_checks_if_event_is_supported(): void
    {
        $this->assertTrue($this->factory->isSupported('subscription.started'));
        $this->assertTrue($this->factory->isSupported('subscription.billing_updated'));
        $this->assertTrue($this->factory->isSupported('subscription.resumed'));
        $this->assertTrue($this->factory->isSupported('subscription.updated'));
        $this->assertTrue($this->factory->isSupported('subscription.update_scheduled'));
        $this->assertTrue($this->factory->isSupported('order.paid'));
        $this->assertTrue($this->factory->isSupported('order.canceled'));
        $this->assertTrue($this->factory->isSupported('order.chargeback_received'));
        $this->assertTrue($this->factory->isSupported('order.chargeback_reversed'));
        $this->assertTrue($this->factory->isSupported('order.payment_failed'));
        $this->assertTrue($this->factory->isSupported('checkout.paid'));
        $this->assertTrue($this->factory->isSupported('checkout.failed'));
        $this->assertTrue($this->factory->isSupported('checkout.canceled'));
        $this->assertTrue($this->factory->isSupported('checkout.expired'));
        $this->assertTrue($this->factory->isSupported('subscription.cancellation_grace_period_completed'));
        $this->assertTrue($this->factory->isSupported('subscription.canceled_for_nonpayment'));
        $this->assertTrue($this->factory->isSupported('refund.completed'));
        $this->assertTrue($this->factory->isSupported('refund.failed'));
        $this->assertTrue($this->factory->isSupported('refund.canceled'));
        $this->assertTrue($this->factory->isSupported('one_off_product.update_submitted'));
        $this->assertTrue($this->factory->isSupported('one_off_product.update_approved'));
        $this->assertTrue($this->factory->isSupported('one_off_product.update_rejected'));
        $this->assertTrue($this->factory->isSupported('one_off_product.archived'));
        $this->assertTrue($this->factory->isSupported('one_off_product.unarchived'));
        $this->assertTrue($this->factory->isSupported('subscription_plan.update_submitted'));
        $this->assertTrue($this->factory->isSupported('subscription_plan.update_approved'));
        $this->assertTrue($this->factory->isSupported('subscription_plan.update_rejected'));
        $this->assertTrue($this->factory->isSupported('subscription_plan.archived'));
        $this->assertTrue($this->factory->isSupported('subscription_plan.unarchived'));
        $this->assertTrue($this->factory->isSupported('webhook.setup'));
        $this->assertFalse($this->factory->isSupported('unknown.event'));
    }

    /**
     * @param array<string, mixed> $object
     */
    private function makeWebhook(
        string $eventName,
        string $entityType,
        string $entityId,
        array $object,
    ): WebhookReceived {
        return new WebhookReceived(
            id: 'webhook_event_'.$entityId,
            resource: 'webhook_event',
            eventName: $eventName,
            entityType: $entityType,
            entityId: $entityId,
            testmode: false,
            createdAt: '2024-01-15T10:00:00Z',
            object: $object,
        );
    }

    /**
     * Build the fat (signed) `order` webhook `object` — byte-shape-equivalent to
     * a `GET /orders/{id}` body, as emitted by vatlify's `OrderPayload::fromModel`.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function fatOrder(array $data): array
    {
        $currency = $data['total']['currency'];

        $object = [
            'id' => $data['id'],
            'resource' => 'order',
            'customerId' => $data['customerId'],
            'createdAt' => '2024-01-15T10:00:00+00:00',
            'testmode' => false,
            'status' => $data['status'],
            'total' => $data['total'],
            'subtotal' => $data['subtotal'],
            'reversedSubtotal' => ['currency' => $currency, 'value' => '0.00'],
            'refundableSubtotal' => $data['subtotal'],
            'invoiceNumber' => $data['invoiceNumber'] ?? null,
            'paymentMethod' => $data['paymentMethod'] ?? null,
            'taxSummary' => $this->fatTaxSummary($data['taxRates'], $currency),
            'metadata' => $data['metadata'] ?? null,
            'lines' => array_map(
                fn (array $line) => [
                    'id' => $line['id'],
                    'resource' => 'orderline',
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'productType' => $line['productType'] ?? null,
                    'productId' => $line['productId'] ?? null,
                    'basePrice' => ['currency' => $currency, 'value' => $line['basePrice']],
                    'total' => ['currency' => $currency, 'value' => $line['total']],
                    'subtotal' => ['currency' => $currency, 'value' => $line['subtotal']],
                    'taxes' => [],
                ],
                $data['lines'] ?? [],
            ),
        ];

        return $object;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function fatRefund(array $data): array
    {
        $currency = $data['total']['currency'];

        return [
            'id' => $data['id'],
            'resource' => 'refund',
            'createdAt' => '2024-01-15T10:00:00+00:00',
            'testmode' => false,
            'status' => $data['status'],
            'customerId' => $data['customerId'],
            'originalOrderId' => $data['originalOrderId'],
            'total' => $data['total'],
            'subtotal' => $data['subtotal'],
            'taxSummary' => $this->fatTaxSummary($data['taxRates'], $currency),
            'lines' => [],
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function fatChargeback(array $data): array
    {
        $currency = $data['total']['currency'];

        return [
            'id' => $data['id'],
            'resource' => 'chargeback',
            'customerId' => $data['customerId'],
            'createdAt' => '2024-01-15T10:00:00+00:00',
            'testmode' => false,
            'status' => $data['status'],
            'amount' => $data['total'],
            'settlementAmount' => $data['total'],
            'total' => $data['total'],
            'subtotal' => $data['subtotal'],
            'taxSummary' => $this->fatTaxSummary($data['taxRates'], $currency),
            'reason' => $data['reason'],
            'originalOrderId' => $data['originalOrderId'],
            'orderId' => null,
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function fatSubscription(array $data): array
    {
        return [
            'id' => $data['id'],
            'resource' => 'subscription',
            'customerId' => $data['customerId'],
            'subscriptionPlanId' => $data['subscriptionPlanId'],
            'testmode' => false,
            'name' => $data['name'],
            'description' => 'A subscription',
            'basePrice' => $data['basePrice'] ?? ['value' => '10.00', 'currency' => 'EUR'],
            'quantity' => $data['quantity'],
            'interval' => $data['interval'] ?? 'month',
            'intervalCount' => 1,
            'status' => 'active',
            'startedAt' => '2024-01-15T10:00:00+00:00',
            'endedAt' => null,
            'canceledAt' => null,
            'renewedAt' => null,
            'renewedUntil' => null,
            'nextRenewalAt' => null,
            'mandate' => $data['mandate'],
        ];
    }

    /**
     * @param array<int, array{name: string, percentage: float, taxablePercentage: float, amount: string}> $taxRates
     *
     * @return array<int, array<string, mixed>>
     */
    private function fatTaxSummary(array $taxRates, string $currency): array
    {
        return array_map(
            fn (array $rate) => [
                'taxRate' => [
                    'name' => $rate['name'],
                    'percentage' => $rate['percentage'],
                    'taxablePercentage' => $rate['taxablePercentage'],
                ],
                'amount' => ['currency' => $currency, 'value' => $rate['amount']],
            ],
            $taxRates,
        );
    }
}
