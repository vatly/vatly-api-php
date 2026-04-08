<?php

namespace Vatly\Tests\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Chargeback;
use Vatly\API\Resources\ChargebackCollection;

class ChargebackEndpointTest extends BaseEndpointTest
{
    /** @test
     * @throws ApiException
     */
    public function can_get_chargeback(): void
    {
        $chargebackId = 'chargeback_dummy_id';
        $responseBodyArray = [
            'id' => $chargebackId,
            'resource' => 'chargeback',
            'merchantId' => 'merchant_123',
            'testmode' => false,
            'amount' => [
                "value" => "100.00",
                "currency" => "EUR",
            ],
            'settlementAmount' => [
                "value" => "-80.00",
                "currency" => "EUR",
            ],
            'reason' => 'reason',
            'createdAt' => '2020-01-01',
            'orderId' => 'order_dummy_id',
            'originalOrderId' => 'original_order_dummy_id',
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/chargebacks/'.$chargebackId,
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

        /** @var Chargeback $chargeback */
        $chargeback = $this->client->chargebacks->get($chargebackId);

        $this->assertInstanceOf(Chargeback::class, $chargeback);
        $this->assertEquals('chargeback', $chargeback->resource);
        $this->assertEquals('chargeback_dummy_id', $chargeback->id);
        $this->assertEquals('merchant_123', $chargeback->merchantId);
        $this->assertEquals('2020-01-01', $chargeback->createdAt);
        $this->assertFalse($chargeback->testmode);
        $this->assertEquals('100.00', $chargeback->amount->value);
        $this->assertEquals('EUR', $chargeback->amount->currency);
        $this->assertEquals('-80.00', $chargeback->settlementAmount->value);
        $this->assertEquals('EUR', $chargeback->settlementAmount->currency);
        $this->assertEquals('reason', $chargeback->reason);
        $this->assertEquals('order_dummy_id', $chargeback->orderId);
        $this->assertEquals('original_order_dummy_id', $chargeback->originalOrderId);
        $this->assertEquals(self::API_ENDPOINT_URL.'/chargebacks/'.$chargebackId, $chargeback->links->self->href);
        $this->assertEquals(self::API_ENDPOINT_URL.'/orders/order_dummy_id', $chargeback->links->order->href);
        $this->assertEquals(self::API_ENDPOINT_URL.'/orders/original_order_dummy_id', $chargeback->links->originalOrder->href);
    }

    /** @test */
    public function get_chargebacks_list(): void
    {
        $responseBodyArray = [
            'count' => 2,
            'data' => [
                ['id' => 'chargeback_123', 'resource' => 'chargeback'],
                ['id' => 'chargeback_456', 'resource' => 'chargeback'],
            ],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/chargebacks',
                    'type' => 'application/hal+json',
                ],
                'next' => [
                    'href' => self::API_ENDPOINT_URL.'/chargebacks?startingAfter=chargeback_next_dummy_id',
                    'type' => 'application/hal+json',
                ],
                'prev' => null,
            ],
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        $chargebackCollection = $this->client->chargebacks->page();

        $this->assertEquals(2, $chargebackCollection->count);
        $this->assertCount(2, $chargebackCollection);
        $this->assertInstanceOf(ChargebackCollection::class, $chargebackCollection);
        $this->assertInstanceOf(Chargeback::class, $chargebackCollection[0]);
        $this->assertInstanceOf(Chargeback::class, $chargebackCollection[1]);
        $this->assertEquals('chargeback', $chargebackCollection[0]->resource);
        $this->assertEquals('chargeback', $chargebackCollection[1]->resource);
        $this->assertEquals('chargeback_123', $chargebackCollection[0]->id);
        $this->assertEquals('chargeback_456', $chargebackCollection[1]->id);

        $this->assertEquals(self::API_ENDPOINT_URL.'/chargebacks', $chargebackCollection->links->self->href);
        $this->assertEquals('application/hal+json', $chargebackCollection->links->self->type);
        $this->assertEquals(self::API_ENDPOINT_URL.'/chargebacks?startingAfter=chargeback_next_dummy_id', $chargebackCollection->links->next->href);
        $this->assertEquals('application/hal+json', $chargebackCollection->links->next->type);
        $this->assertNull($chargebackCollection->links->prev);

        $this->assertNull($chargebackCollection->previous());
    }

    /** @test */
    public function can_get_previous_page(): void
    {
        $responseBodyArrayCollection = [
            [
                'count' => 1,
                'data' => [
                    ['id' => 'chargeback_123', 'resource' => 'chargeback'],
                ],
                'links' => [
                    'self' => [
                        'href' => self::API_ENDPOINT_URL.'/chargebacks?startingAfter=chargeback_next_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'next' => null,
                    'prev' => [
                        'href' => self::API_ENDPOINT_URL.'/chargebacks?endingBefore=chargeback_previous_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                ],
            ],
            [
                'count' => 1,
                'data' => [
                    ['id' => 'chargeback_456', 'resource' => 'chargeback',],
                ],
                'links' => [
                    'self' => [
                        'href' => self::API_ENDPOINT_URL.'/chargebacks?startingAfter=chargeback_previous_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'next' => [
                        'href' => self::API_ENDPOINT_URL.'/chargebacks?startingAfter=chargeback_next_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'prev' => [
                        'href' => self::API_ENDPOINT_URL.'/chargebacks',
                        'type' => 'application/hal+json',
                    ],
                ],
            ],
        ];

        $this->httpClient->setSendReturnCollectionFromArray($responseBodyArrayCollection);

        $chargebackCollection = $this->client->chargebacks->page();

        $previousChargebackCollection = $chargebackCollection->previous();

        $this->assertEquals(1, $previousChargebackCollection->count);
        $this->assertCount(1, $previousChargebackCollection);
        $this->assertInstanceOf(ChargebackCollection::class, $previousChargebackCollection);

        $chargeback = $previousChargebackCollection[0];
        $this->assertInstanceOf(Chargeback::class, $chargeback);
        $this->assertEquals('chargeback', $chargeback->resource);
        $this->assertEquals('chargeback_456', $chargeback->id);
    }
}
