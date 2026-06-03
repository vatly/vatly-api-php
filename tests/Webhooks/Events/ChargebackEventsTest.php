<?php

declare(strict_types=1);

namespace Vatly\Tests\Webhooks\Events;

use Vatly\API\Resources\Chargeback;
use Vatly\API\Types\Money;
use Vatly\API\Types\TaxSummaryCollection;
use Vatly\API\Webhooks\Events\OrderChargebackReceived;
use Vatly\API\Webhooks\Events\OrderChargebackReversed;
use Vatly\API\Webhooks\Events\WebhookReceived;
use Vatly\Tests\BaseTestCase;

class ChargebackEventsTest extends BaseTestCase
{
    private function makeApiChargeback(string $status = 'charged_back'): Chargeback
    {
        $chargeback = new Chargeback($this->client);
        $chargeback->id = 'chb_123';
        $chargeback->customerId = 'cus_456';
        $chargeback->originalOrderId = 'ord_789';
        $chargeback->status = $status;
        $chargeback->reason = 'fraudulent';
        $chargeback->total = new Money('EUR', '24.20');
        $chargeback->subtotal = new Money('EUR', '20.00');
        $chargeback->taxSummary = new TaxSummaryCollection([
            [
                'taxRate' => ['name' => 'VAT', 'percentage' => 21.0, 'taxablePercentage' => 100.0],
                'amount' => ['currency' => 'EUR', 'value' => '4.20'],
            ],
        ]);

        return $chargeback;
    }

    private function makeWebhook(string $eventName): WebhookReceived
    {
        return new WebhookReceived(
            id: 'webhook_event_1',
            resource: 'webhook_event',
            eventName: $eventName,
            entityType: 'order',
            entityId: 'ord_789',
            testmode: true,
            createdAt: '2024-01-01T10:00:00+00:00',
            object: [
                'id' => 'chb_123',
                'originalOrderId' => 'ord_789',
                'reason' => 'fraudulent',
            ],
        );
    }

    public function test_event_name_constants(): void
    {
        $this->assertSame('order.chargeback_received', OrderChargebackReceived::VATLY_EVENT_NAME);
        $this->assertSame('order.chargeback_reversed', OrderChargebackReversed::VATLY_EVENT_NAME);
    }

    public function test_received_builds_from_api_chargeback_with_tax_breakdown(): void
    {
        $chargeback = $this->makeApiChargeback('charged_back');

        $event = OrderChargebackReceived::fromApiChargeback($chargeback);

        $this->assertSame('ord_789', $event->orderId);
        $this->assertSame('chb_123', $event->chargebackId);
        $this->assertSame('ord_789', $event->originalOrderId);
        $this->assertSame('fraudulent', $event->reason);
        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('charged_back', $event->status);
        $this->assertInstanceOf(Money::class, $event->total);
        $this->assertSame('24.20', $event->total->value);
        $this->assertSame(2420, $event->total->toCents());
        $this->assertInstanceOf(Money::class, $event->subtotal);
        $this->assertSame(2000, $event->subtotal->toCents());
        $this->assertSame('EUR', $event->currency);
        $this->assertSame($chargeback->taxSummary, $event->taxSummary);
        $this->assertCount(1, $event->taxSummary->items);
    }

    public function test_received_degrades_to_sparse_webhook_payload(): void
    {
        $event = OrderChargebackReceived::fromWebhook($this->makeWebhook('order.chargeback_received'));

        $this->assertSame('ord_789', $event->orderId);
        $this->assertSame('chb_123', $event->chargebackId);
        $this->assertSame('ord_789', $event->originalOrderId);
        $this->assertSame('fraudulent', $event->reason);
        $this->assertSame('', $event->customerId);
        $this->assertSame('', $event->status);
        $this->assertNull($event->total);
        $this->assertNull($event->subtotal);
        $this->assertNull($event->taxSummary);
    }

    public function test_reversed_builds_from_api_chargeback(): void
    {
        $chargeback = $this->makeApiChargeback('charged_back_reversed');

        $event = OrderChargebackReversed::fromApiChargeback($chargeback);

        $this->assertSame('charged_back_reversed', $event->status);
        $this->assertSame(2420, $event->total->toCents());
        $this->assertSame($chargeback->taxSummary, $event->taxSummary);
    }

    public function test_reversed_degrades_to_sparse_webhook_payload(): void
    {
        $event = OrderChargebackReversed::fromWebhook($this->makeWebhook('order.chargeback_reversed'));

        $this->assertSame('chb_123', $event->chargebackId);
        $this->assertNull($event->taxSummary);
    }

    public function test_reason_falls_back_to_null_when_empty(): void
    {
        $chargeback = $this->makeApiChargeback();
        $chargeback->reason = '';

        $event = OrderChargebackReceived::fromApiChargeback($chargeback);

        $this->assertNull($event->reason);
    }
}
