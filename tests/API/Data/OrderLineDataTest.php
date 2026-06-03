<?php

declare(strict_types=1);

namespace Vatly\Tests\API\Data;

use Vatly\API\Data\OrderLineData;
use Vatly\API\Resources\OrderLine;
use Vatly\API\Types\Money;
use Vatly\API\Types\TaxSummaryCollection;
use Vatly\Tests\BaseTestCase;

class OrderLineDataTest extends BaseTestCase
{
    private function makeApiLine(
        string $id = 'order_item_abc',
        string $description = 'Pro plan — monthly',
        int $quantity = 1,
        string $basePrice = '20.00',
        string $total = '24.20',
        string $subtotal = '20.00',
        ?string $productType = 'subscription',
        ?string $productId = 'subscription_abc',
    ): OrderLine {
        $line = new OrderLine($this->client);
        $line->id = $id;
        $line->resource = 'orderline';
        $line->description = $description;
        $line->quantity = $quantity;
        $line->productType = $productType;
        $line->productId = $productId;
        $line->basePrice = new Money('EUR', $basePrice);
        $line->total = new Money('EUR', $total);
        $line->subtotal = new Money('EUR', $subtotal);
        $line->taxes = new TaxSummaryCollection([
            [
                'taxRate' => ['name' => 'VAT', 'percentage' => 21.0, 'taxablePercentage' => 100.0],
                'amount' => ['currency' => 'EUR', 'value' => '4.20'],
            ],
        ]);

        return $line;
    }

    public function test_it_maps_an_api_order_line_to_cents_and_carries_product_fields(): void
    {
        $line = $this->makeApiLine(
            id: 'order_item_sub',
            description: 'Pro plan — monthly',
            quantity: 2,
            basePrice: '20.00',
            total: '24.20',
            subtotal: '20.00',
            productType: 'subscription',
            productId: 'subscription_abc',
        );

        $data = OrderLineData::fromApiOrderLine($line);

        $this->assertSame('order_item_sub', $data->vatlyId);
        $this->assertSame('Pro plan — monthly', $data->description);
        $this->assertSame(2, $data->quantity);
        $this->assertSame(2000, $data->basePrice);
        $this->assertSame(2420, $data->total);
        $this->assertSame(2000, $data->subtotal);
        $this->assertSame('subscription', $data->productType);
        $this->assertSame('subscription_abc', $data->productId);
        $this->assertInstanceOf(TaxSummaryCollection::class, $data->taxSummary);
        $this->assertCount(1, $data->taxSummary->items);
        $this->assertSame('VAT', $data->taxSummary->items[0]->taxRate->name);
        $this->assertSame('4.20', $data->taxSummary->items[0]->amount->value);
    }

    public function test_it_carries_null_product_attribution(): void
    {
        $line = $this->makeApiLine(productType: null, productId: null);

        $data = OrderLineData::fromApiOrderLine($line);

        $this->assertNull($data->productType);
        $this->assertNull($data->productId);
    }

    public function test_it_passes_through_the_api_tax_summary_collection_unchanged(): void
    {
        $line = $this->makeApiLine();

        $data = OrderLineData::fromApiOrderLine($line);

        $this->assertSame($line->taxes, $data->taxSummary);
    }
}
