<?php

declare(strict_types=1);

namespace Vatly\Tests\Webhooks\Events;

use Vatly\API\Resources\Refund;
use Vatly\API\Types\Money;
use Vatly\API\Types\TaxSummaryCollection;
use Vatly\API\Webhooks\Events\RefundCanceled;
use Vatly\API\Webhooks\Events\RefundCompleted;
use Vatly\API\Webhooks\Events\RefundFailed;
use Vatly\Tests\BaseTestCase;

class RefundEventsTest extends BaseTestCase
{
    private function makeApiRefund(string $status = 'refunded'): Refund
    {
        $refund = new Refund($this->client);
        $refund->id = 'refund_123';
        $refund->status = $status;
        $refund->customerId = 'cus_456';
        $refund->originalOrderId = 'ord_789';
        $refund->testmode = true;
        $refund->total = new Money('EUR', '24.20');
        $refund->subtotal = new Money('EUR', '20.00');
        $refund->taxSummary = new TaxSummaryCollection([
            [
                'taxRate' => ['name' => 'VAT', 'percentage' => 21.0, 'taxablePercentage' => 100.0],
                'amount' => ['currency' => 'EUR', 'value' => '4.20'],
            ],
        ]);

        return $refund;
    }

    public function test_event_name_constants(): void
    {
        $this->assertSame('refund.completed', RefundCompleted::VATLY_EVENT_NAME);
        $this->assertSame('refund.failed', RefundFailed::VATLY_EVENT_NAME);
        $this->assertSame('refund.canceled', RefundCanceled::VATLY_EVENT_NAME);
    }

    public function test_refund_completed_builds_from_api_refund(): void
    {
        $refund = $this->makeApiRefund('refunded');

        $event = RefundCompleted::fromApiRefund($refund);

        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('refund_123', $event->refundId);
        $this->assertSame('refunded', $event->status);
        $this->assertInstanceOf(Money::class, $event->total);
        $this->assertSame('24.20', $event->total->value);
        $this->assertSame(2420, $event->total->toCents());
        $this->assertSame('EUR', $event->total->currency);
        $this->assertInstanceOf(Money::class, $event->subtotal);
        $this->assertSame('20.00', $event->subtotal->value);
        $this->assertSame(2000, $event->subtotal->toCents());
        $this->assertSame('ord_789', $event->originalOrderId);
        $this->assertTrue($event->testmode);
        $this->assertSame($refund->taxSummary, $event->taxSummary);
        $this->assertCount(1, $event->taxSummary->items);
        $this->assertSame('VAT', $event->taxSummary->items[0]->taxRate->name);
    }

    public function test_refund_failed_builds_from_api_refund(): void
    {
        $event = RefundFailed::fromApiRefund($this->makeApiRefund('failed'));

        $this->assertSame('failed', $event->status);
        $this->assertSame(2420, $event->total->toCents());
        $this->assertInstanceOf(TaxSummaryCollection::class, $event->taxSummary);
    }

    public function test_refund_canceled_builds_from_api_refund(): void
    {
        $event = RefundCanceled::fromApiRefund($this->makeApiRefund('canceled'));

        $this->assertSame('canceled', $event->status);
        $this->assertSame(2000, $event->subtotal->toCents());
        $this->assertInstanceOf(TaxSummaryCollection::class, $event->taxSummary);
    }
}
