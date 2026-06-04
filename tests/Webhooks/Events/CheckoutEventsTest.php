<?php

declare(strict_types=1);

namespace Vatly\Tests\Webhooks\Events;

use PHPUnit\Framework\TestCase;
use Vatly\API\Webhooks\Events\CheckoutCanceled;
use Vatly\API\Webhooks\Events\CheckoutExpired;
use Vatly\API\Webhooks\Events\CheckoutFailed;
use Vatly\API\Webhooks\Events\CheckoutPaid;
use Vatly\API\Webhooks\Events\WebhookReceived;

class CheckoutEventsTest extends TestCase
{
    /**
     * @param array<string, mixed> $object
     */
    private function makeWebhook(string $eventName, array $object): WebhookReceived
    {
        return new WebhookReceived(
            id: 'webhook_event_1',
            resource: 'webhook_event',
            eventName: $eventName,
            entityType: 'checkout',
            entityId: 'checkout_123',
            testmode: true,
            createdAt: '2024-01-01T10:00:00+00:00',
            object: $object,
        );
    }

    public function test_event_name_constants(): void
    {
        $this->assertSame('checkout.paid', CheckoutPaid::VATLY_EVENT_NAME);
        $this->assertSame('checkout.failed', CheckoutFailed::VATLY_EVENT_NAME);
        $this->assertSame('checkout.canceled', CheckoutCanceled::VATLY_EVENT_NAME);
        $this->assertSame('checkout.expired', CheckoutExpired::VATLY_EVENT_NAME);
    }

    public function test_checkout_paid_builds_from_webhook(): void
    {
        $event = CheckoutPaid::fromWebhook($this->makeWebhook('checkout.paid', [
            'customerId' => 'cus_456',
            'orderId' => 'ord_789',
            'status' => 'paid',
            'metadata' => ['cart_id' => 'cart_1'],
        ]));

        $this->assertSame('checkout_123', $event->checkoutId);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('ord_789', $event->orderId);
        $this->assertSame('paid', $event->status);
        $this->assertTrue($event->testmode);
        $this->assertSame(['cart_id' => 'cart_1'], $event->metadata);
    }

    public function test_checkout_paid_defaults_status_and_nullable_fields(): void
    {
        $event = CheckoutPaid::fromWebhook($this->makeWebhook('checkout.paid', []));

        $this->assertNull($event->customerId);
        $this->assertNull($event->orderId);
        $this->assertSame('paid', $event->status);
        $this->assertNull($event->metadata);
    }

    public function test_checkout_failed_defaults_status(): void
    {
        $event = CheckoutFailed::fromWebhook($this->makeWebhook('checkout.failed', []));

        $this->assertSame('failed', $event->status);
    }

    public function test_checkout_canceled_defaults_status(): void
    {
        $event = CheckoutCanceled::fromWebhook($this->makeWebhook('checkout.canceled', []));

        $this->assertSame('canceled', $event->status);
    }

    public function test_checkout_expired_defaults_status(): void
    {
        $event = CheckoutExpired::fromWebhook($this->makeWebhook('checkout.expired', []));

        $this->assertSame('expired', $event->status);
    }

    public function test_checkout_paid_normalizes_object_metadata(): void
    {
        $event = CheckoutPaid::fromWebhook($this->makeWebhook('checkout.paid', [
            'metadata' => (object) ['cart_id' => 'cart_2'],
        ]));

        $this->assertSame(['cart_id' => 'cart_2'], $event->metadata);
    }
}
