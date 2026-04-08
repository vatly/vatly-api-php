<?php

namespace Vatly\Tests\Endpoints;

use Vatly\API\Resources\Customer;
use Vatly\API\Resources\CustomerCollection;
use Vatly\API\VatlyApiClient;

class CustomersEndpointTest extends BaseEndpointTest
{
    /** @test */
    public function it_can_create_a_customer(): void
    {
        $responseBodyArray = [
            'id' => 'customer_78b146a7de7d417e9d68d7e6ef193d18',
            'resource' => 'customer',
            'email' => 'testcustomer@dummy.com',
            'createdAt' => '2020-01-01T00:00:00+00:00',
            'testmode' => true,
            'metadata' => [
                'customer_id' => '123456',
            ],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL. '/customers/customer_78b146a7de7d417e9d68d7e6ef193d18',
                    'type' => 'application/hal+json',
                ],
            ],
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        /** @var Customer $customer */
        $customer = $this->client->customers->create([
            'email' => 'testcustomer@dummy.com',
            'metadata' => [
                'customer_id' => '123456',
            ],
        ]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL."/customers",
            [],
            '{"email":"testcustomer@dummy.com","metadata":{"customer_id":"123456"}}'
        );

        $this->assertEquals('customer_78b146a7de7d417e9d68d7e6ef193d18', $customer->id);
        $this->assertEquals('customer', $customer->resource);
        $this->assertEquals('testcustomer@dummy.com', $customer->email);
        $this->assertEquals('2020-01-01T00:00:00+00:00', $customer->createdAt);
        $this->assertTrue($customer->testmode);
        $this->assertEquals(['customer_id' => '123456'], (array) $customer->metadata);
        $this->assertEquals(self::API_ENDPOINT_URL. '/customers/customer_78b146a7de7d417e9d68d7e6ef193d18', $customer->links->self->href);
        $this->assertEquals('application/hal+json', $customer->links->self->type);
    }

    /** @test */
    public function it_creates_customer_with_minimal_data(): void
    {
        $responseBodyArray = [
            'id' => 'customer_78b146a7de7d417e9d68d7e6ef193d18',
            'resource' => 'customer',
            'createdAt' => '2020-01-01T00:00:00+00:00',
            'testmode' => true,
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL. '/customers/customer_78b146a7de7d417e9d68d7e6ef193d18',
                    'type' => 'application/hal+json',
                ],
            ],
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        /** @var Customer $customer */
        $customer = $this->client->customers->create([
            'email' => 'testcustomer@dummy.com',
        ]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL."/customers",
            [],
            '{"email":"testcustomer@dummy.com"}'
        );

        $this->assertEquals('customer_78b146a7de7d417e9d68d7e6ef193d18', $customer->id);
        $this->assertEquals('customer', $customer->resource);
        $this->assertEquals('2020-01-01T00:00:00+00:00', $customer->createdAt);
        $this->assertTrue($customer->testmode);
        $this->assertEquals(self::API_ENDPOINT_URL. '/customers/customer_78b146a7de7d417e9d68d7e6ef193d18', $customer->links->self->href);
        $this->assertEquals('application/hal+json', $customer->links->self->type);
        $this->assertNull($customer->email);
        $this->assertNull($customer->metadata);
    }

    /** @test */
    public function it_can_get_a_customer(): void
    {
        $responseBodyArray = [
            'id' => 'customer_78b146a7de7d417e9d68d7e6ef193d18',
            'resource' => 'customer',
            'email' => 'testcustomer@dummy.com',
            'createdAt' => '2020-01-01T00:00:00+00:00',
            'testmode' => true,
            'metadata' => [
                'customer_id' => '123456',
            ],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL . '/customers/customer_78b146a7de7d417e9d68d7e6ef193d18',
                    'type' => 'application/hal+json',
                ],
            ],
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        /** @var Customer $customer */
        $customer = $this->client->customers->get('customer_78b146a7de7d417e9d68d7e6ef193d18');

        $this->assertEquals('customer_78b146a7de7d417e9d68d7e6ef193d18', $customer->id);
        $this->assertEquals('customer', $customer->resource);
        $this->assertEquals('testcustomer@dummy.com', $customer->email);
    }

    /** @test */
    public function can_get_customers_list(): void
    {
        $responseBodyArray = [
            'count' => 2,
            'data' => [
                [
                    'id' => 'customer_78b146a7de7d417e9d68d7e6ef193d18',
                    'resource' => 'customer',
                    'email' => 'testcustomer@dummy.com',
                    'createdAt' => '2020-01-01T00:00:00+00:00',
                    'testmode' => true,
                    'metadata' => [
                        'customer_id' => '123456',
                    ],
                    'links' => [
                        'self' => [
                            'href' => self::API_ENDPOINT_URL . '/customers/customer_78b146a7de7d417e9d68d7e6ef193d18',
                            'type' => 'application/hal+json',
                        ],
                    ],
                ],
                [
                    'id' => 'customer_78b146a7de7d417e9d68d7e6ef193d19',
                    'resource' => 'customer',
                    'email' => 'johndoe@example.com',
                    'createdAt' => '2020-01-01T00:00:00+00:00',
                    'testmode' => true,
                    'metadata' => null,
                    'links' => [
                        'self' => [
                            'href' => self::API_ENDPOINT_URL . '/customers/customer_78b146a7de7d417e9d68d7e6ef193d19',
                            'type' => 'application/hal+json',
                        ],
                    ],
                ],
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
                'prev' => [
                    'href' => self::API_ENDPOINT_URL . '/customers?endingBefore=customer_78b146a7de7d417e9d68d7e6ef193d18',
                    'type' => 'application/hal+json',
                ],
            ],

        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        /** @var CustomerCollection $customers */
        $customers = $this->client->customers->page();

        $this->assertEquals(2, $customers->count);
        $this->assertEquals(self::API_ENDPOINT_URL . '/customers', $customers->links->self->href);
        $this->assertEquals('application/hal+json', $customers->links->self->type);

        $customer1 = $customers[0];
        $this->assertEquals('customer_78b146a7de7d417e9d68d7e6ef193d18', $customer1->id);
        $this->assertEquals('customer', $customer1->resource);
        $this->assertEquals('testcustomer@dummy.com', $customer1->email);

        $customer2 = $customers[1];
        $this->assertEquals('customer_78b146a7de7d417e9d68d7e6ef193d19', $customer2->id);
        $this->assertEquals('customer', $customer2->resource);
        $this->assertEquals('johndoe@example.com', $customer2->email);

        $this->assertEquals(self::API_ENDPOINT_URL . '/customers/customer_78b146a7de7d417e9d68d7e6ef193d18', $customer1->links->self->href);
        $this->assertEquals(self::API_ENDPOINT_URL . '/customers/customer_78b146a7de7d417e9d68d7e6ef193d19', $customer2->links->self->href);
        $this->assertEquals(self::API_ENDPOINT_URL . '/customers?startingAfter=customer_78b146a7de7d417e9d68d7e6ef193d19', $customers->links->next->href);
        $this->assertEquals(self::API_ENDPOINT_URL . '/customers?endingBefore=customer_78b146a7de7d417e9d68d7e6ef193d18', $customers->links->prev->href);
    }

    /** @test */
    public function can_get_next_page():void
    {
        $responseBodyArrayCollection = [
            [
                'count' => 1,
                'data' => [
                    ['id' => 'customer_78b146a7de7d417e9d68d7e6ef193d18', 'resource' => 'customer'],
                ],
                'links' => [
                    'self' => [
                        'href' => self::API_ENDPOINT_URL . '/customers',
                        'type' => 'application/hal+json',
                    ],
                    'next' => [
                        'href' => self::API_ENDPOINT_URL . '/customers?startingAfter=customer_78b146a7de7d417e9d68d7e6ef193d18',
                        'type' => 'application/hal+json',
                    ],
                    'prev' => [
                        'href' => self::API_ENDPOINT_URL . '/customers?endingBefore=customer_previous_id',
                        'type' => 'application/hal+json',
                    ],
                ],
            ],
            [
                'count' => 1,
                'data' => [
                    [
                        'id' => 'customer_78b146a7de7d417e9d68d7e6ef193d19',
                        'resource' => 'customer',
                        'email' => 'me@me.com',
                        'createdAt' => '2020-01-01T00:00:00+00:00',
                        'testmode' => true,
                        'metadata' => null,
                        'links' => [
                            'self' => [
                                'href' => self::API_ENDPOINT_URL . '/customers/customer_78b146a7de7d417e9d68d7e6ef193d19',
                                'type' => 'application/hal+json',
                            ],
                        ],
                    ],
                ],
                'links' => [
                    'self' => [
                        'href' => self::API_ENDPOINT_URL . '/customers?startingAfter=customer_78b146a7de7d417e9d68d7e6ef193d18',
                        'type' => 'application/hal+json',
                    ],
                    'next' => null,
                    'prev' => [
                        'href' => self::API_ENDPOINT_URL . '/customers?endingBefore=customer_78b146a7de7d417e9d68d7e6ef193d18',
                        'type' => 'application/hal+json',
                    ],
                ],
            ],
        ];

        $this->httpClient->setSendReturnCollectionFromArray($responseBodyArrayCollection);

        /** @var CustomerCollection $customers */
        $customers = $this->client->customers->page();

        /** @var CustomerCollection $nextCustomers */
        $nextCustomers = $customers->next();

        $this->assertWasSent(
            VatlyApiClient::HTTP_GET,
            self::API_ENDPOINT_URL . '/customers?startingAfter=customer_78b146a7de7d417e9d68d7e6ef193d18',
            [],
            null,
        );

        $customer = $nextCustomers[0];

        $this->assertEquals(1, $nextCustomers->count);
        $this->assertEquals(self::API_ENDPOINT_URL . '/customers?startingAfter=customer_78b146a7de7d417e9d68d7e6ef193d18', $nextCustomers->links->self->href);
        $this->assertEquals('application/hal+json', $nextCustomers->links->self->type);
        $this->assertNull($nextCustomers->next());

        $this->assertEquals('customer_78b146a7de7d417e9d68d7e6ef193d19', $customer->id);
        $this->assertEquals('customer', $customer->resource);
        $this->assertEquals('me@me.com', $customer->email);
        $this->assertEquals('2020-01-01T00:00:00+00:00', $customer->createdAt);
        $this->assertTrue($customer->testmode);
        $this->assertEquals(null, $customer->metadata);
        $this->assertEquals(self::API_ENDPOINT_URL . '/customers/customer_78b146a7de7d417e9d68d7e6ef193d19', $customer->links->self->href);
        $this->assertEquals('application/hal+json', $customer->links->self->type);
    }
}
