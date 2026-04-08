<?php

namespace Vatly\Tests\Endpoints;

use Vatly\API\Resources\Customer;
use Vatly\API\Resources\CustomerCollection;

class AutoPaginatorTest extends BaseEndpointTest
{
    /** @test */
    public function test_auto_pagination_forward(): void
    {
        $responseBodyArrayCollection =
            [
                [
                    'count' => 2,
                    'data' => [
                        ['id' => 'customer_78b146a7de7d417e9d68d7e6ef193d18', 'resource' => 'customer'],
                        ['id' => 'customer_78b146a7de7d417e9d68d7e6ef193d19', 'resource' => 'customer'],
                    ],
                    'links' => [
                        'self' => [
                            'href' => self::API_ENDPOINT_URL . '/customers',
                            'type' => 'application/hal+json',
                        ],
                        'next' => [
                            'href' => self::API_ENDPOINT_URL . '/customers?startingAfter=customer_78b146a7de7d417e9d68d7e6ef193d19',
                            'type' => 'application/hal+json',
                        ],
                        'prev' => null,
                    ],
                ],
                [
                    'count' => 2,
                    'data' => [
                        ['id' => 'customer_78b146a7de7d417e9d68d7e6ef193d20', 'resource' => 'customer'],
                        ['id' => 'customer_78b146a7de7d417e9d68d7e6ef193d21', 'resource' => 'customer'],
                    ],
                    'links' => [
                        'self' => [
                            'href' => self::API_ENDPOINT_URL . '/customers?startingAfter=customer_78b146a7de7d417e9d68d7e6ef193d19',
                            'type' => 'application/hal+json',
                        ],
                        'next' => [
                            'href' => self::API_ENDPOINT_URL . '/customers?startingAfter=customer_78b146a7de7d417e9d68d7e6ef193d21',
                            'type' => 'application/hal+json',
                        ],
                        'prev' => [
                            'href' => self::API_ENDPOINT_URL . '/customers?endingBefore=customer_78b146a7de7d417e9d68d7e6ef193d20',
                            'type' => 'application/hal+json',
                        ],
                    ],
                ],
                [
                    'count' => 2,
                    'data' => [
                        ['id' => 'customer_78b146a7de7d417e9d68d7e6ef193d22', 'resource' => 'customer'],
                        ['id' => 'customer_78b146a7de7d417e9d68d7e6ef193d23', 'resource' => 'customer'],
                    ],
                    'links' => [
                        'self' => [
                            'href' => self::API_ENDPOINT_URL . '/customers?startingAfter=customer_78b146a7de7d417e9d68d7e6ef193d21',
                            'type' => 'application/hal+json',
                        ],
                        'next' => null,
                        'prev' => [
                            'href' => self::API_ENDPOINT_URL . '/customers?endingBefore=customer_78b146a7de7d417e9d68d7e6ef193d22',
                            'type' => 'application/hal+json',
                        ],
                    ],
                ],
            ];

        $this->httpClient->setSendReturnCollectionFromArray($responseBodyArrayCollection);


        /** @var CustomerCollection $customers */
        $customers = $this->client->customers->page(null, null, 2);

        $totalItemsReceived = 0;

        foreach ($customers->autoPagingIterator() as $customer) {
            $this->assertInstanceOf(Customer::class, $customer);
            $totalItemsReceived++;
        }

        $this->assertEquals(6, $totalItemsReceived);
    }

    /** @test */
    public function test_auto_pagination_backward()
    {
        $responseBodyArrayCollection =
            [

                [
                    'count' => 2,
                    'data' => [
                        ['id' => 'customer_78b146a7de7d417e9d68d7e6ef193d22', 'resource' => 'customer'],
                        ['id' => 'customer_78b146a7de7d417e9d68d7e6ef193d23', 'resource' => 'customer'],
                    ],
                    'links' => [
                        'self' => [
                            'href' => self::API_ENDPOINT_URL . '/customers?endingBefore=customer_78b146a7de7d417e9d68d7e6ef193d24',
                            'type' => 'application/hal+json',
                        ],
                        'next' => null,
                        'prev' => [
                            'href' => self::API_ENDPOINT_URL . '/customers?endingBefore=customer_78b146a7de7d417e9d68d7e6ef193d22',
                            'type' => 'application/hal+json',
                        ],
                    ],
                ],
                [
                    'count' => 2,
                    'data' => [
                        ['id' => 'customer_78b146a7de7d417e9d68d7e6ef193d20', 'resource' => 'customer'],
                        ['id' => 'customer_78b146a7de7d417e9d68d7e6ef193d21', 'resource' => 'customer'],
                    ],
                    'links' => [
                        'self' => [
                            'href' => self::API_ENDPOINT_URL . '/customers?endingBefore=customer_78b146a7de7d417e9d68d7e6ef193d22',
                            'type' => 'application/hal+json',
                        ],
                        'next' => [
                            'href' => self::API_ENDPOINT_URL . '/customers?startingAfter=customer_78b146a7de7d417e9d68d7e6ef193d21',
                            'type' => 'application/hal+json',
                        ],
                        'prev' => [
                            'href' => self::API_ENDPOINT_URL . '/customers?endingBefore=customer_78b146a7de7d417e9d68d7e6ef193d20',
                            'type' => 'application/hal+json',
                        ],
                    ],
                ],
                [
                    'count' => 2,
                    'data' => [
                        ['id' => 'customer_78b146a7de7d417e9d68d7e6ef193d18', 'resource' => 'customer'],
                        ['id' => 'customer_78b146a7de7d417e9d68d7e6ef193d19', 'resource' => 'customer'],
                    ],
                    'links' => [
                        'self' => [
                            'href' => self::API_ENDPOINT_URL . '/customers?endingBefore=customer_78b146a7de7d417e9d68d7e6ef193d20',
                            'type' => 'application/hal+json',
                        ],
                        'next' => [
                            'href' => self::API_ENDPOINT_URL . '/customers?startingAfter=customer_78b146a7de7d417e9d68d7e6ef193d19',
                            'type' => 'application/hal+json',
                        ],
                        'prev' => null,
                    ],
                ],
            ];

        $this->httpClient->setSendReturnCollectionFromArray($responseBodyArrayCollection);


        /** @var CustomerCollection $customers */
        $customers = $this->client->customers->page(
            'customer_78b146a7de7d417e9d68d7e6ef193d24',
            null,
            2
        );

        $totalItemsReceived = 0;

        foreach ($customers->autoPagingIterator() as $customer) {
            $this->assertInstanceOf(Customer::class, $customer);
            $totalItemsReceived++;
        }

        $this->assertEquals(6, $totalItemsReceived);
    }
}
