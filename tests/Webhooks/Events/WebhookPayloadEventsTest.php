<?php

declare(strict_types=1);

namespace Vatly\Tests\Webhooks\Events;

use PHPUnit\Framework\TestCase;
use Vatly\API\Webhooks\Events\OrderCanceled;
use Vatly\API\Webhooks\Events\UnsupportedWebhookReceived;
use Vatly\API\Webhooks\Events\WebhookReceived;

class WebhookPayloadEventsTest extends TestCase
{
    /**
     * @param array<string, mixed> $object
     */
    private function makeWebhook(string $eventName, string $entityType, string $entityId, array $object): WebhookReceived
    {
        return new WebhookReceived(
            id: 'webhook_event_1',
            resource: 'webhook_event',
            eventName: $eventName,
            entityType: $entityType,
            entityId: $entityId,
            testmode: true,
            createdAt: '2024-01-01T10:00:00+00:00',
            object: $object,
        );
    }

    public function test_order_canceled_constant(): void
    {
        $this->assertSame('order.canceled', OrderCanceled::VATLY_EVENT_NAME);
    }

    public function test_order_canceled_builds_from_webhook(): void
    {
        $event = OrderCanceled::fromWebhook($this->makeWebhook('order.canceled', 'order', 'ord_123', [
            'customerId' => 'cus_456',
            'status' => 'canceled',
        ]));

        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('ord_123', $event->orderId);
        $this->assertSame('canceled', $event->status);
    }

    public function test_order_canceled_defaults(): void
    {
        $event = OrderCanceled::fromWebhook($this->makeWebhook('order.canceled', 'order', 'ord_123', []));

        $this->assertSame('', $event->customerId);
        $this->assertSame('canceled', $event->status);
    }

    public function test_webhook_received_carries_full_payload_and_helpers(): void
    {
        $webhook = $this->makeWebhook('order.paid', 'order', 'ord_123', ['customerId' => 'cus_456']);

        $this->assertSame('webhook_event_1', $webhook->id);
        $this->assertSame('order.paid', $webhook->eventName);
        $this->assertSame('cus_456', $webhook->getCustomerId());
        $this->assertSame('order.paid', $webhook->toArray()['eventName']);
        $this->assertSame(['customerId' => 'cus_456'], $webhook->toArray()['object']);
    }

    public function test_webhook_received_get_customer_id_returns_null_when_absent(): void
    {
        $webhook = $this->makeWebhook('order.paid', 'order', 'ord_123', []);

        $this->assertNull($webhook->getCustomerId());
    }

    public function test_unsupported_webhook_received_maps_all_fields_from_webhook(): void
    {
        $webhook = $this->makeWebhook('some.unknown_event', 'mystery', 'mys_123', ['foo' => 'bar']);

        $event = UnsupportedWebhookReceived::fromWebhook($webhook);

        $this->assertSame('webhook_event_1', $event->id);
        $this->assertSame('webhook_event', $event->resource);
        $this->assertSame('some.unknown_event', $event->eventName);
        $this->assertSame('mystery', $event->entityType);
        $this->assertSame('mys_123', $event->entityId);
        $this->assertTrue($event->testmode);
        $this->assertSame('2024-01-01T10:00:00+00:00', $event->createdAt);
        $this->assertSame(['foo' => 'bar'], $event->object);
    }
}
