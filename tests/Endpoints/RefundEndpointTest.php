<?php

namespace Vatly\Tests\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Refund;
use Vatly\API\Resources\RefundCollection;
use Vatly\API\Resources\RefundLine;
use Vatly\API\Types\RefundStatus;
use Vatly\API\VatlyApiClient;

class RefundEndpointTest extends BaseEndpointTest
{
    /** @test
     * @throws ApiException
     */
    public function can_get_refund(): void
    {
        $refundId = 'refund_dummy_id';
        $responseBodyArray = [
            'id' => $refundId,
            'resource' => 'refund',
            'merchantId' => 'merchant_123',
            'customerId' => 'customer_123',
            'testmode' => false,
            'status' => RefundStatus::REFUNDED,
            'metadata' => [
                'refund_id' => '123456',
            ],
            'orderId' => 'order_dummy_id',
            'originalOrderId' => 'original_order_dummy_id',
            'createdAt' => '2023-01-11T10:50:50+02:00',
            'total' => [
                "value" => "96.00",
                "currency" => "EUR",
            ],
            'subtotal' => [
                "value" => "80.00",
                "currency" => "EUR",
            ],
            'taxSummary' => [
                [
                    'taxRate' => ['name' => 'VAT', 'percentage' => 21, 'taxablePercentage' => 100],
                    'amount' => ['value' => '16.00', 'currency' => 'EUR'],
                ],
            ],
            'lines' => [
                [
                    "id" => "refund_item_2a46f4c01d3b47979f4d7b3f58c98be7",
                    "resource" => "refundline",
                    "description" => "PDF Book",
                    "quantity" => 1,
                    "basePrice" => [
                        "value" => "80.00",
                        "currency" => "EUR",
                    ],
                    "total" => [
                        "value" => "96.00",
                        "currency" => "EUR",
                    ],
                    "taxAmount" => [
                        "value" => "20.00",
                        "currency" => "EUR",
                    ],
                    "subtotal" => [
                        "value" => "80.00",
                        "currency" => "EUR",
                    ],
                    'taxes' => [
                        [
                            'taxRate' => ['name' => 'VAT', 'percentage' => 21, 'taxablePercentage' => 100],
                            'amount' => ['value' => '16.00', 'currency' => 'EUR'],
                        ],
                    ],
                ],
            ],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/refunds/'.$refundId,
                    'type' => 'application/hal+json',
                ],
                'order' => [
                    'href' => self::API_ENDPOINT_URL.'/orders/order_dummy_id',
                    'type' => 'application/hal+json',
                ],
                'originalOrder' => [
                    'href' => self::API_ENDPOINT_URL.'/orders/original_order_dummy_id',
                    'type' => 'application/hal+json',
                ],
            ],
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        /** @var Refund $refund */
        $refund = $this->client->refunds->get($refundId, []);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_GET,
            self::API_ENDPOINT_URL.'/refunds/'.$refundId,
            [],
            null
        );

        $this->assertEquals($refundId, $refund->id);
        $this->assertEquals('refund', $refund->resource);
        $this->assertEquals('merchant_123', $refund->merchantId);
        $this->assertEquals('customer_123', $refund->customerId);
        $this->assertEquals('order_dummy_id', $refund->orderId);
        $this->assertEquals('original_order_dummy_id', $refund->originalOrderId);
        $this->assertFalse($refund->testmode);
        $this->assertEquals(RefundStatus::REFUNDED, $refund->status);
        $this->assertEquals('96.00', $refund->total->value);
        $this->assertEquals('80.00', $refund->subtotal->value);
        $this->assertCount(1, $refund->taxSummary->items);
        $this->assertEquals('VAT', $refund->taxSummary->items[0]->taxRate->name);
        $this->assertEquals(21, $refund->taxSummary->items[0]->taxRate->percentage);
        $this->assertEquals(100, $refund->taxSummary->items[0]->taxRate->taxablePercentage);
        $this->assertEquals('16.00', $refund->taxSummary->items[0]->amount->value);
        $this->assertEquals('EUR', $refund->taxSummary->items[0]->amount->currency);
        $this->assertEquals('2023-01-11T10:50:50+02:00', $refund->createdAt);

        $this->assertEquals('https://api.vatly.com/v1/refunds/refund_dummy_id', $refund->links->self->href);
        $this->assertEquals('application/hal+json', $refund->links->self->type);
        $this->assertEquals('https://api.vatly.com/v1/orders/order_dummy_id', $refund->links->order->href);
        $this->assertEquals('application/hal+json', $refund->links->order->type);
        $this->assertEquals('https://api.vatly.com/v1/orders/original_order_dummy_id', $refund->links->originalOrder->href);
        $this->assertEquals('application/hal+json', $refund->links->originalOrder->type);

        $this->assertEquals(1, $refund->lines()->count());

        /** @var RefundLine $refundLine */
        $refundLine = $refund->lines()[0];
        $this->assertEquals('refund_item_2a46f4c01d3b47979f4d7b3f58c98be7', $refundLine->id);
        $this->assertEquals('refundline', $refundLine->resource);
        $this->assertEquals('PDF Book', $refundLine->description);
        $this->assertEquals("96.00", $refundLine->total->value);
        $this->assertEquals("80.00", $refundLine->subtotal->value);
        $this->assertEquals("80.00", $refundLine->basePrice->value);
        $this->assertCount(1, $refundLine->taxes->items);
        $this->assertEquals("VAT", $refundLine->taxes->items[0]->taxRate->name);
        $this->assertEquals(21, $refundLine->taxes->items[0]->taxRate->percentage);
        $this->assertEquals(100, $refundLine->taxes->items[0]->taxRate->taxablePercentage);
        $this->assertEquals("16.00", $refundLine->taxes->items[0]->amount->value);
        $this->assertEquals("EUR", $refundLine->taxes->items[0]->amount->currency);
    }

    /** @test */
    public function get_refunds_list(): void
    {
        $responseBodyArray = [
            'count' => 2,
            'data' => [
                [
                    'id' => 'refund_123',
                    'resource' => 'refund',
                ],
                [
                    'id' => 'refund_456',
                    'resource' => 'refund',
                ],
            ],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/refunds',
                    'type' => 'application/hal+json',
                ],
                'next' => [
                    'href' => self::API_ENDPOINT_URL.'/refunds?startingAfter=refund_next_dummy_id',
                    'type' => 'application/hal+json',
                ],
                'prev' => null,
            ],
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        $refundCollection = $this->client->refunds->page();


        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_GET,
            self::API_ENDPOINT_URL.'/refunds?',
            [],
            null
        );

        $this->assertEquals(2, $refundCollection->count);
        $this->assertCount(2, $refundCollection);
        $this->assertInstanceOf(RefundCollection::class, $refundCollection);
        $this->assertInstanceOf(Refund::class, $refundCollection[0]);
        $this->assertInstanceOf(Refund::class, $refundCollection[1]);
        $this->assertEquals('refund', $refundCollection[0]->resource);
        $this->assertEquals('refund', $refundCollection[1]->resource);
        $this->assertEquals('refund_123', $refundCollection[0]->id);
        $this->assertEquals('refund_456', $refundCollection[1]->id);

        $this->assertEquals(self::API_ENDPOINT_URL.'/refunds', $refundCollection->links->self->href);
        $this->assertEquals('application/hal+json', $refundCollection->links->self->type);
        $this->assertEquals(self::API_ENDPOINT_URL.'/refunds?startingAfter=refund_next_dummy_id', $refundCollection->links->next->href);
        $this->assertEquals('application/hal+json', $refundCollection->links->next->type);
        $this->assertNull($refundCollection->links->prev);

        $this->assertNull($refundCollection->previous());
    }

    /** @test */
    public function can_get_previous_page(): void
    {
        $responseBodyArrayCollection = [
            [
                'count' => 1,
                'data' => [
                    [
                        'id' => 'refund_123',
                        'resource' => 'refund',
                    ],
                ],
                'links' => [
                    'self' => [
                        'href' => self::API_ENDPOINT_URL.'/refunds?startingAfter=refund_next_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'next' => null,
                    'prev' => [
                        'href' => self::API_ENDPOINT_URL.'/refunds?endingBefore=refund_previous_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                ],
            ],
            [
                'count' => 1,
                'data' => [
                    [
                        'id' => 'refund_456',
                        'resource' => 'refund',
                    ],
                ],
                'links' => [
                    'self' => [
                        'href' => self::API_ENDPOINT_URL.'/refunds?endingBefore=refund_previous_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'next' => [
                        'href' => self::API_ENDPOINT_URL.'/refunds?startingAfter=refund_next_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'prev' => [
                        'href' => self::API_ENDPOINT_URL.'/refunds',
                        'type' => 'application/hal+json',
                    ],
                ],
            ],
        ];

        $this->httpClient->setSendReturnCollectionFromArray($responseBodyArrayCollection);

        $refundCollection = $this->client->refunds->page();

        $previousRefundCollection = $refundCollection->previous();


        $this->assertWasSent(
            VatlyApiClient::HTTP_GET,
            self::API_ENDPOINT_URL.'/refunds?endingBefore=refund_previous_dummy_id',
            [],
            null
        );

        $this->assertEquals(1, $previousRefundCollection->count);
        $this->assertCount(1, $previousRefundCollection);
        $this->assertInstanceOf(RefundCollection::class, $previousRefundCollection);

        $refund = $previousRefundCollection[0];
        $this->assertInstanceOf(Refund::class, $refund);
        $this->assertEquals('refund', $refund->resource);
        $this->assertEquals('refund_456', $refund->id);
    }
}
