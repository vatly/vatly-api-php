<?php

namespace Vatly\Tests\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Chargeback;
use Vatly\API\Resources\ChargebackCollection;

class OrderChargebackEndpointTest extends BaseEndpointTest
{
    /** @test
     * @throws ApiException
     */
    public function can_get_a_order_chargeback(): void
    {
        $chargebackId = 'chargeback_dummy_id';
        $originalOrderId = 'original_order_dummy_id';
        $responseBodyArray = [
            'id' => $chargebackId,
            'resource' => 'chargeback',
            'testmode' => false,
            'originalOrderId' => $originalOrderId,
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        /** @var Chargeback $chargeback */
        $chargeback = $this->client->orderChargebacks->getForOrderId($originalOrderId, $chargebackId);
        $this->assertInstanceOf(Chargeback::class, $chargeback);
        $this->assertEquals('chargeback', $chargeback->resource);
        $this->assertEquals('chargeback_dummy_id', $chargeback->id);
        $this->assertFalse($chargeback->testmode);
        $this->assertEquals('original_order_dummy_id', $chargeback->originalOrderId);

        $this->assertWasSentOnly(
            'GET',
            self::API_ENDPOINT_URL.'/orders/'.$originalOrderId.'/chargebacks/'.$chargebackId,
            [],
            null
        );
    }

    /** @test */
    public function get_order_chargebacks_list(): void
    {
        $originalOrderId = 'original_order_dummy_id';

        $responseBodyArray = [
            'count' => 2,
            'data' => [
                [
                    'id' => 'chargeback_123',
                    'resource' => 'chargeback',
                    'originalOrderId' => $originalOrderId,
                ],
                [
                    'id' => 'chargeback_456',
                    'resource' => 'chargeback',
                    'originalOrderId' => $originalOrderId,
                ],
            ],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/orders/'.$originalOrderId.'/chargebacks',
                    'type' => 'application/hal+json',
                ],
                'next' => [
                    'href' => self::API_ENDPOINT_URL.'/orders/'.$originalOrderId.'/chargebacks?startingAfter=chargeback_next_dummy_id',
                    'type' => 'application/hal+json',
                ],
                'prev' => null,
            ],
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        $chargebackCollection = $this->client->orderChargebacks->pageForOrderId($originalOrderId);

        $this->assertEquals(2, $chargebackCollection->count);
        $this->assertCount(2, $chargebackCollection);
        $this->assertInstanceOf(ChargebackCollection::class, $chargebackCollection);
        $this->assertInstanceOf(Chargeback::class, $chargebackCollection[0]);
        $this->assertEquals('chargeback', $chargebackCollection[0]->resource);
        $this->assertEquals('chargeback_123', $chargebackCollection[0]->id);
        $this->assertEquals($originalOrderId, $chargebackCollection[0]->originalOrderId);

        $this->assertEquals(self::API_ENDPOINT_URL.'/orders/'.$originalOrderId.'/chargebacks', $chargebackCollection->links->self->href);
        $this->assertEquals('application/hal+json', $chargebackCollection->links->self->type);
        $this->assertEquals(self::API_ENDPOINT_URL.'/orders/'.$originalOrderId.'/chargebacks?startingAfter=chargeback_next_dummy_id', $chargebackCollection->links->next->href);
        $this->assertEquals('application/hal+json', $chargebackCollection->links->next->type);
        $this->assertNull($chargebackCollection->links->prev);

        $this->assertNull($chargebackCollection->previous());

        $this->assertWasSentOnly(
            'GET',
            self::API_ENDPOINT_URL.'/orders/'.$originalOrderId.'/chargebacks?',
            [],
            null
        );
    }

    /** @test */
    public function can_get_previous_page(): void
    {
        $originalOrderId = 'original_order_dummy_id';

        $responseBodyArrayCollection = [
            [
                'count' => 1,
                'data' => [
                    [
                        'id' => 'chargeback_123',
                        'resource' => 'chargeback',
                        'originalOrderId' => $originalOrderId,
                    ],
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
                    [
                        'id' => 'chargeback_456',
                        'resource' => 'chargeback',
                    ],
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

        $chargebackCollection = $this->client->orderChargebacks->pageForOrderId($originalOrderId);

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
