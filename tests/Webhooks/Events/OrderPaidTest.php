<?php

declare(strict_types=1);

namespace Vatly\Tests\Webhooks\Events;

use Vatly\API\Data\OrderLineData;
use Vatly\API\Resources\Order;
use Vatly\API\Types\Money;
use Vatly\API\Types\TaxSummaryCollection;
use Vatly\API\Types\WebhookEventName;
use Vatly\API\Webhooks\Events\OrderPaid;
use Vatly\Tests\BaseTestCase;

class OrderPaidTest extends BaseTestCase
{
    private function makeApiOrder(): Order
    {
        $order = new Order($this->client);
        $order->id = 'ord_123';
        $order->customerId = 'cus_456';
        $order->status = 'paid';
        $order->total = new Money('USD', '49.99');
        $order->subtotal = new Money('USD', '41.31');
        $order->invoiceNumber = 'INV-2024-002';
        $order->paymentMethod = 'ideal';
        $order->metadata = null;
        $order->taxSummary = new TaxSummaryCollection([
            [
                'taxRate' => ['name' => 'VAT', 'percentage' => 21.0, 'taxablePercentage' => 100.0],
                'amount' => ['currency' => 'USD', 'value' => '8.68'],
            ],
        ]);
        $order->lines = [];

        return $order;
    }

    /**
     * Build a raw API-shaped order line (the wire shape `Order::lines()`
     * hydrates), not a pre-built resource.
     *
     * @return object
     */
    private function makeApiLine(
        string $id,
        string $description,
        int $quantity,
        string $basePrice,
        string $total,
        string $subtotal,
        ?string $productType,
        ?string $productId,
    ): object {
        return (object) json_decode((string) json_encode([
            'id' => $id,
            'resource' => 'orderline',
            'description' => $description,
            'quantity' => $quantity,
            'productType' => $productType,
            'productId' => $productId,
            'basePrice' => ['currency' => 'EUR', 'value' => $basePrice],
            'total' => ['currency' => 'EUR', 'value' => $total],
            'subtotal' => ['currency' => 'EUR', 'value' => $subtotal],
            'taxes' => [
                [
                    'taxRate' => ['name' => 'VAT', 'percentage' => 21.0, 'taxablePercentage' => 100.0],
                    'amount' => ['currency' => 'EUR', 'value' => '4.20'],
                ],
            ],
        ]));
    }

    public function test_it_has_correct_vatly_event_name_constant(): void
    {
        $this->assertSame('order.paid', OrderPaid::VATLY_EVENT_NAME);
        $this->assertSame(WebhookEventName::ORDER_PAID, OrderPaid::VATLY_EVENT_NAME);
    }

    public function test_it_builds_from_api_order_resource_with_tax_breakdown(): void
    {
        $event = OrderPaid::fromApiOrder($this->makeApiOrder());

        $this->assertSame('cus_456', $event->customerId);
        $this->assertSame('ord_123', $event->orderId);
        $this->assertSame('paid', $event->status);
        $this->assertSame(4999, $event->total);
        $this->assertSame(4131, $event->subtotal);
        $this->assertSame('USD', $event->currency);
        $this->assertSame('INV-2024-002', $event->invoiceNumber);
        $this->assertSame('ideal', $event->paymentMethod);
        $this->assertInstanceOf(TaxSummaryCollection::class, $event->taxSummary);
        $this->assertCount(1, $event->taxSummary->items);
        $this->assertSame('VAT', $event->taxSummary->items[0]->taxRate->name);
        $this->assertSame('8.68', $event->taxSummary->items[0]->amount->value);
    }

    public function test_it_passes_through_the_api_tax_summary_collection(): void
    {
        $order = $this->makeApiOrder();

        $event = OrderPaid::fromApiOrder($order);

        $this->assertSame($order->taxSummary, $event->taxSummary);
    }

    public function test_it_builds_from_api_order_with_no_customer_or_invoice(): void
    {
        $order = $this->makeApiOrder();
        $order->customerId = null;
        $order->invoiceNumber = null;
        $order->paymentMethod = null;
        $order->total = new Money('GBP', '15.00');
        $order->subtotal = new Money('GBP', '12.40');
        $order->taxSummary = new TaxSummaryCollection([]);

        $event = OrderPaid::fromApiOrder($order);

        $this->assertSame('', $event->customerId);
        $this->assertNull($event->invoiceNumber);
        $this->assertNull($event->paymentMethod);
        $this->assertSame(1500, $event->total);
        $this->assertSame(1240, $event->subtotal);
        $this->assertSame('GBP', $event->currency);
        $this->assertCount(0, $event->taxSummary->items);
        $this->assertNull($event->metadata);
    }

    public function test_it_carries_metadata_from_api_order_array(): void
    {
        $order = $this->makeApiOrder();
        $order->metadata = ['vatly_transaction_id' => 'tx_42', 'source' => 'checkout'];

        $event = OrderPaid::fromApiOrder($order);

        $this->assertSame(
            ['vatly_transaction_id' => 'tx_42', 'source' => 'checkout'],
            $event->metadata,
        );
    }

    public function test_it_normalizes_object_metadata_from_api_order(): void
    {
        $order = $this->makeApiOrder();
        $order->metadata = (object) ['vatly_transaction_id' => 'tx_42'];

        $event = OrderPaid::fromApiOrder($order);

        $this->assertSame(['vatly_transaction_id' => 'tx_42'], $event->metadata);
    }

    public function test_it_maps_api_order_lines_with_product_fields_and_money_to_cents(): void
    {
        $order = $this->makeApiOrder();
        $order->lines = [
            $this->makeApiLine('order_item_sub', 'Pro plan — monthly', 1, '20.00', '24.20', '20.00', 'subscription', 'subscription_abc'),
            $this->makeApiLine('order_item_addon', 'Seat add-on', 3, '5.00', '18.15', '15.00', 'one_off_product', 'product_seat'),
        ];

        $event = OrderPaid::fromApiOrder($order);

        $this->assertCount(2, $event->lines);
        $this->assertContainsOnlyInstancesOf(OrderLineData::class, $event->lines);

        $sub = $event->lines[0];
        $this->assertSame('order_item_sub', $sub->vatlyId);
        $this->assertSame('Pro plan — monthly', $sub->description);
        $this->assertSame(1, $sub->quantity);
        $this->assertSame(2000, $sub->basePrice);
        $this->assertSame(2420, $sub->total);
        $this->assertSame(2000, $sub->subtotal);
        $this->assertSame('subscription', $sub->productType);
        $this->assertSame('subscription_abc', $sub->productId);
        $this->assertInstanceOf(TaxSummaryCollection::class, $sub->taxSummary);
        $this->assertCount(1, $sub->taxSummary->items);
        $this->assertSame('VAT', $sub->taxSummary->items[0]->taxRate->name);

        $addon = $event->lines[1];
        $this->assertSame('order_item_addon', $addon->vatlyId);
        $this->assertSame(3, $addon->quantity);
        $this->assertSame(500, $addon->basePrice);
        $this->assertSame(1815, $addon->total);
        $this->assertSame(1500, $addon->subtotal);
        $this->assertSame('one_off_product', $addon->productType);
        $this->assertSame('product_seat', $addon->productId);
    }

    public function test_it_maps_a_line_with_null_product_attribution(): void
    {
        $order = $this->makeApiOrder();
        $order->lines = [
            $this->makeApiLine('order_item_unattributed', 'Legacy line', 1, '10.00', '10.00', '10.00', null, null),
        ];

        $event = OrderPaid::fromApiOrder($order);

        $this->assertCount(1, $event->lines);
        $this->assertNull($event->lines[0]->productType);
        $this->assertNull($event->lines[0]->productId);
    }

    public function test_it_defaults_to_empty_lines(): void
    {
        $event = OrderPaid::fromApiOrder($this->makeApiOrder());

        $this->assertSame([], $event->lines);
    }
}
