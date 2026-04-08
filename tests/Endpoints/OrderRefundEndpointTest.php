<?php

namespace Vatly\Tests\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Order;
use Vatly\API\Resources\Refund;
use Vatly\API\Resources\RefundCollection;
use Vatly\API\VatlyApiClient;

class OrderRefundEndpointTest extends BaseEndpointTest
{
    /** @test
     * @throws ApiException
     */
    public function can_get_an_order_refund(): void
    {
        $refundId = 'refund_dummy_id';
        $originalOrderId = 'original_order_dummy_id';
        $responseBodyArray = [
            'id' => $refundId,
            'resource' => 'refund',
            'testmode' => false,
            'originalOrderId' => $originalOrderId,
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        /** @var Refund $refund */
        $refund = $this->client->orderRefunds->getForOrderId($originalOrderId, $refundId);
        $this->assertInstanceOf(Refund::class, $refund);
        $this->assertEquals('refund', $refund->resource);
        $this->assertEquals('refund_dummy_id', $refund->id);
        $this->assertFalse($refund->testmode);
        $this->assertEquals('original_order_dummy_id', $refund->originalOrderId);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_GET,
            self::API_ENDPOINT_URL.'/orders/'.$originalOrderId.'/refunds/'.$refundId,
            [],
            null
        );
    }

    /** @test */
    public function get_order_refunds_list(): void
    {
        $originalOrderId = 'original_order_dummy_id';

        $responseBodyArray = [
            'count' => 2,
            'data' => [
                [
                    'id' => 'refund_123',
                    'resource' => 'refund',
                    'originalOrderId' => $originalOrderId,
                ],
                [
                    'id' => 'refund_456',
                    'resource' => 'refund',
                    'originalOrderId' => $originalOrderId,
                ],
            ],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/orders/'.$originalOrderId.'/refunds',
                    'type' => 'application/hal+json',
                ],
                'next' => [
                    'href' => self::API_ENDPOINT_URL.'/orders/'.$originalOrderId.'/refunds?startingAfter=refund_next_dummy_id',
                    'type' => 'application/hal+json',
                ],
                'prev' => null,
            ],
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        $refundCollection = $this->client->orderRefunds->pageForOrderId($originalOrderId);

        $this->assertEquals(2, $refundCollection->count);
        $this->assertCount(2, $refundCollection);
        $this->assertInstanceOf(RefundCollection::class, $refundCollection);
        $this->assertInstanceOf(Refund::class, $refundCollection[0]);
        $this->assertEquals('refund', $refundCollection[0]->resource);
        $this->assertEquals('refund_123', $refundCollection[0]->id);
        $this->assertEquals($originalOrderId, $refundCollection[0]->originalOrderId);

        $this->assertEquals(self::API_ENDPOINT_URL.'/orders/'.$originalOrderId.'/refunds', $refundCollection->links->self->href);
        $this->assertEquals('application/hal+json', $refundCollection->links->self->type);
        $this->assertEquals(self::API_ENDPOINT_URL.'/orders/'.$originalOrderId.'/refunds?startingAfter=refund_next_dummy_id', $refundCollection->links->next->href);
        $this->assertEquals('application/hal+json', $refundCollection->links->next->type);
        $this->assertNull($refundCollection->links->prev);

        $this->assertNull($refundCollection->previous());

        $this->assertWasSentOnly(
            'GET',
            self::API_ENDPOINT_URL.'/orders/'.$originalOrderId.'/refunds?',
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
                        'id' => 'refund_123',
                        'resource' => 'refund',
                        'originalOrderId' => $originalOrderId,
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
                        'href' => self::API_ENDPOINT_URL.'/refunds?startingAfter=refund_previous_dummy_id',
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

        $refundCollection = $this->client->orderRefunds->pageForOrderId($originalOrderId);

        $previousRefundCollection = $refundCollection->previous();

        $this->assertEquals(1, $previousRefundCollection->count);
        $this->assertCount(1, $previousRefundCollection);
        $this->assertInstanceOf(RefundCollection::class, $previousRefundCollection);

        $refund = $previousRefundCollection[0];
        $this->assertInstanceOf(Refund::class, $refund);
        $this->assertEquals('refund', $refund->resource);
        $this->assertEquals('refund_456', $refund->id);
    }

    /** @test */
    public function it_can_create_a_refund(): void
    {
        $responseBodyArray = [
            'id' => 'refund_dummy_id',
            'resource' => 'refund',
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        $refund = $this->client->orderRefunds->createForOrderId('original_order_dummy_id', [
            'items' => [
                'itemId' => 'item_dummy_id',
                'amount' => [
                    'value' => '100.00',
                    'currency' => 'eur',
                ],
                'description' => 'Item description',
            ],
            'metadata' => [
                'refund_id' => '123456',
            ],
        ]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/orders/original_order_dummy_id/refunds',
            [],
            '{"items":{"itemId":"item_dummy_id","amount":{"value":"100.00","currency":"eur"},"description":"Item description"},"metadata":{"refund_id":"123456"}}'
        );

        $this->assertInstanceOf(Refund::class, $refund);
        $this->assertEquals('refund', $refund->resource);
    }

    /** @test */
    public function it_can_create_a_full_refund(): void
    {
        $responseBodyArray = [
            'id' => 'refund_dummy_id',
            'resource' => 'refund',
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        $refund = $this->client->orderRefunds->createFullRefundForOrderId('original_order_dummy_id', [
            'metadata' => [
                'refund_id' => '123456',
            ],
        ]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/orders/original_order_dummy_id/refunds/full',
            [],
            '{"metadata":{"refund_id":"123456"}}'
        );

        $this->assertInstanceOf(Refund::class, $refund);
        $this->assertEquals('refund', $refund->resource);
    }

    /** @test */
    public function it_can_create_a_refund_directly_from_an_order(): void
    {
        $responseBodyArray = [
            'id' => 'refund_dummy_id',
            'resource' => 'refund',
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        $order = new Order($this->client);
        $order->id = 'original_order_dummy_id';

        $refund = $order->refund([
            'items' => [
                'itemId' => 'item_dummy_id',
                'amount' => [
                    'value' => '100.00',
                    'currency' => 'eur',
                ],
                'description' => 'Item description',
            ],
            'metadata' => [
                'refund_id' => '123456',
            ],
        ]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/orders/original_order_dummy_id/refunds',
            [],
            '{"items":{"itemId":"item_dummy_id","amount":{"value":"100.00","currency":"eur"},"description":"Item description"},"metadata":{"refund_id":"123456"}}'
        );

        $this->assertInstanceOf(Refund::class, $refund);
        $this->assertEquals('refund', $refund->resource);
    }

    /** @test */
    public function it_can_create_a_full_refund_directly_from_an_order(): void
    {
        $responseBodyArray = [
            'id' => 'refund_dummy_id',
            'resource' => 'refund',
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        $order = new Order($this->client);
        $order->id = 'original_order_dummy_id';

        $refund = $order->fullRefund([
            'metadata' => [
                'refund_id' => '123456',
            ],
        ]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/orders/original_order_dummy_id/refunds/full',
            [],
            '{"metadata":{"refund_id":"123456"}}'
        );

        $this->assertInstanceOf(Refund::class, $refund);
        $this->assertEquals('refund', $refund->resource);
    }

    /** @test */
    public function it_can_cancel_a_refund() : void
    {
        $refundId = 'refund_dummy_id';
        $originalOrderId = 'original_order_dummy_id';

        $this->httpClient->setSendReturnNull();

        $this->client->orderRefunds->cancelRefundForOrderId($originalOrderId, $refundId);

        $this->assertWasSentOnly(
            'DELETE',
            self::API_ENDPOINT_URL.'/orders/'.$originalOrderId.'/refunds/'.$refundId,
            [],
            null
        );
    }

    /** @test */
    public function it_can_be_canceled_directly_from_the_refund() : void
    {
        $refundId = 'refund_dummy_id';
        $originalOrderId = 'original_order_dummy_id';

        $this->httpClient->setSendReturnNull();

        $refund = new Refund($this->client);
        $refund->id = $refundId;
        $refund->originalOrderId = $originalOrderId;
        $refund->cancel();

        $this->assertWasSentOnly(
            'DELETE',
            self::API_ENDPOINT_URL.'/orders/'.$originalOrderId.'/refunds/'.$refundId,
            [],
            null
        );
    }
}
