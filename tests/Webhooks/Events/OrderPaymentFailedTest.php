<?php

declare(strict_types=1);

namespace Vatly\Tests\Webhooks\Events;

use Vatly\API\Resources\Order;
use Vatly\API\Types\Money;
use Vatly\API\Types\TaxSummaryCollection;
use Vatly\API\Webhooks\Events\OrderPaymentFailed;
use Vatly\Tests\BaseTestCase;

class OrderPaymentFailedTest extends BaseTestCase
{
    private function makeApiOrder(): Order
    {
        $order = new Order($this->client);
        $order->id = 'ord_failed';
        $order->customerId = 'cus_456';
        $order->status = 'pending';
        $order->total = new Money('EUR', '99.00');
        $order->subtotal = new Money('EUR', '81.82');
        $order->invoiceNumber = 'INV-2024-009';
        $order->paymentMethod = 'creditcard';
        $order->metadata = null;
        $order->taxSummary = new TaxSummaryCollection([
            [
                'taxRate' => ['name' => 'VAT', 'percentage' => 21.0, 'taxablePercentage' => 100.0],
                'amount' => ['currency' => 'EUR', 'value' => '17.18'],
            ],
        ]);

        return $order;
    }

    public function test_it_has_correct_vatly_event_name_constant(): void
    {
        $this->assertSame('order.payment_failed', OrderPaymentFailed::VATLY_EVENT_NAME);
    }

    public function test_it_builds_from_api_order_resource(): void
    {
        $event = OrderPaymentFailed::fromApiOrder($this->makeApiOrder());

        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('ord_failed', $event->orderId);
        $this->assertSame('pending', $event->status);
        $this->assertInstanceOf(Money::class, $event->total);
        $this->assertSame('99.00', $event->total->value);
        $this->assertSame(9900, $event->total->toCents());
        $this->assertSame('EUR', $event->total->currency);
        $this->assertInstanceOf(Money::class, $event->subtotal);
        $this->assertSame('81.82', $event->subtotal->value);
        $this->assertSame(8182, $event->subtotal->toCents());
        $this->assertSame('INV-2024-009', $event->invoiceNumber);
        $this->assertSame('creditcard', $event->paymentMethod);
        $this->assertInstanceOf(TaxSummaryCollection::class, $event->taxSummary);
        $this->assertCount(1, $event->taxSummary->items);
    }

    public function test_it_passes_through_the_api_tax_summary_collection(): void
    {
        $order = $this->makeApiOrder();

        $event = OrderPaymentFailed::fromApiOrder($order);

        $this->assertSame($order->taxSummary, $event->taxSummary);
    }

    public function test_it_normalizes_object_metadata(): void
    {
        $order = $this->makeApiOrder();
        $order->metadata = (object) ['dunning_attempt' => 1];

        $event = OrderPaymentFailed::fromApiOrder($order);

        $this->assertSame(['dunning_attempt' => 1], $event->metadata);
    }
}
