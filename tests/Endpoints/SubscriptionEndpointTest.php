<?php

namespace Vatly\Tests\Endpoints;

use Vatly\API\Resources\ResourceFactory;
use Vatly\API\Resources\Subscription;
use Vatly\API\Resources\SubscriptionCollection;
use Vatly\API\Types\SubscriptionStatus;
use Vatly\API\VatlyApiClient;

class SubscriptionEndpointTest extends BaseEndpointTest
{
    /** @test */
    public function can_get_subscription()
    {
        $subscriptionId = 'subscription_78b146a7de7d417e9d68d7e6ef193d18';

        $responseBodyArray = $this->subscriptionDemoData($subscriptionId);

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        /** @var Subscription $subscription */
        $subscription = $this->client->subscriptions->get($subscriptionId);
        $this->assertInstanceOf(Subscription::class, $subscription);

        $this->assertEquals('subscription', $subscription->resource);
        $this->assertEquals($subscriptionId, $subscription->id);
        $this->assertEquals('Test subscription', $subscription->name);
        $this->assertEquals('Test subscription description', $subscription->description);
        $this->assertEquals('10.00', $subscription->basePrice->value);
        $this->assertEquals('EUR', $subscription->basePrice->currency);
        $this->assertEquals(1, $subscription->quantity);
        $this->assertEquals('month', $subscription->interval);
        $this->assertEquals(1, $subscription->intervalCount);
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertTrue($subscription->testmode);
        $this->assertEquals('2023-01-11T10:50:50+02:00', $subscription->startedAt);
        $this->assertNull($subscription->endedAt);
        $this->assertNull($subscription->cancelledAt);
        $this->assertNull($subscription->renewedAt);
        $this->assertNull($subscription->renewedUntil);
        $this->assertEquals('2023-02-11T10:50:50+02:00', $subscription->nextRenewalAt);
        $this->assertEquals('US', $subscription->billingAddress->country);
        $this->assertEquals('New York', $subscription->billingAddress->city);
        $this->assertTrue($subscription->isActive());
        $this->assertFalse($subscription->isCanceled());
        $this->assertFalse($subscription->isOnGracePeriod());
        $this->assertFalse($subscription->isTrial());

        $this->assertEquals(self::API_ENDPOINT_URL. '/subscriptions/' . $subscriptionId, $subscription->links->self->href);
        $this->assertEquals('application/hal+json', $subscription->links->self->type);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_GET,
            self::API_ENDPOINT_URL.'/subscriptions/'.$subscriptionId,
            [],
            null
        );
    }

    /** @test */
    public function can_list_subscriptions()
    {
        $responseBodyArray = [
            'count' => 2,
            'data' => [
                ['id' => 'subscription_123', 'resource' => 'subscription'],
                ['id' => 'subscription_456', 'resource' => 'subscription'],
            ],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/subscriptions',
                    'type' => 'application/hal+json',
                ],
                'next' => [
                    'href' => self::API_ENDPOINT_URL.'/subscriptions?startingAfter=subscription_next_dummy_id',
                    'type' => 'application/hal+json',
                ],
                'prev' => [
                    'href' => self::API_ENDPOINT_URL.'/subscriptions?endingBefore=subscription_previous_dummy_id',
                    'type' => 'application/hal+json',
                ],
            ],
        ];


        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        $subscriptionCollection = $this->client->subscriptions->page();

        $this->assertEquals(2, $subscriptionCollection->count);
        $this->assertCount(2, $subscriptionCollection);
        $this->assertInstanceOf(SubscriptionCollection::class, $subscriptionCollection);
        $this->assertInstanceOf(Subscription::class, $subscriptionCollection[0]);
        $this->assertInstanceOf(Subscription::class, $subscriptionCollection[1]);

        $this->assertEquals('subscription_123', $subscriptionCollection[0]->id);
        $this->assertEquals('subscription_456', $subscriptionCollection[1]->id);

        $this->assertEquals(self::API_ENDPOINT_URL.'/subscriptions', $subscriptionCollection->links->self->href);
        $this->assertEquals('application/hal+json', $subscriptionCollection->links->self->type);
        $this->assertEquals(self::API_ENDPOINT_URL.'/subscriptions?startingAfter=subscription_next_dummy_id', $subscriptionCollection->links->next->href);
        $this->assertEquals('application/hal+json', $subscriptionCollection->links->next->type);
        $this->assertEquals(self::API_ENDPOINT_URL.'/subscriptions?endingBefore=subscription_previous_dummy_id', $subscriptionCollection->links->prev->href);
        $this->assertEquals('application/hal+json', $subscriptionCollection->links->prev->type);


        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_GET,
            self::API_ENDPOINT_URL.'/subscriptions?',
            [],
            null
        );
    }

    /** @test */
    public function can_get_next_page_of_subscriptions()
    {
        $responseBodyArrayCollection = [
            [
                'count' => 2,
                'data' => [
                    ['id' => 'subscription_123', 'resource' => 'subscription',],
                    ['id' => 'subscription_456', 'resource' => 'subscription',],
                ],
                'links' => [
                    'self' => [
                        'href' => self::API_ENDPOINT_URL . '/subscriptions',
                        'type' => 'application/hal+json',
                    ],
                    'next' => [
                        'href' => self::API_ENDPOINT_URL . '/subscriptions?startingAfter=subscription_next_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'prev' => null,
                ],
            ],
            [
                'count' => 1,
                'data' => [
                    ['id' => 'subscription_789', 'resource' => 'subscription',],
                ],
                'links' => [
                    'self' => [
                        'href' => self::API_ENDPOINT_URL . '/subscriptions?startingAfter=subscription_next_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'next' => null,
                    'prev' => [
                        'href' => self::API_ENDPOINT_URL . '/subscriptions',
                        'type' => 'application/hal+json',
                    ],
                ],
            ],
        ];

        $this->httpClient->setSendReturnCollectionFromArray($responseBodyArrayCollection);

        $subscriptionCollection = $this->client->subscriptions->page();

        $nextProductCollection = $subscriptionCollection->next();

        $this->assertEquals(1, $nextProductCollection->count);
        $this->assertCount(1, $nextProductCollection);
        $this->assertInstanceOf(SubscriptionCollection::class, $nextProductCollection);

        $subscription = $nextProductCollection[0];
        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals('subscription_789', $subscription->id);

        $this->assertNull($nextProductCollection->next());
    }

    /** @test */
    public function can_cancel_subscription()
    {
        /** @var Subscription $subscription */
        $subscription = ResourceFactory::createResourceFromApiResult((object) $this->subscriptionDemoData('subscription_123'), new Subscription($this->client));

        $this->httpClient->setSendReturnObjectFromArray($this->subscriptionDemoData('subscription_123', SubscriptionStatus::CANCELED));
        $subscription->cancel();

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_DELETE,
            self::API_ENDPOINT_URL.'/subscriptions/subscription_123',
            [],
            null
        );
    }

    /** @test */
    public function can_update_billing_details()
    {
        /** @var Subscription $subscription */
        $subscription = ResourceFactory::createResourceFromApiResult((object) $this->subscriptionDemoData('subscription_123'), new Subscription($this->client));

        $this->httpClient->setSendReturnObjectFromArray(['href' => self::WEBSITE_ENDPOINT_URL.'/checkout/checkout_dummy_id/update', 'type' => 'text/html']);
        $updatedBilling = [
            'streetAndNumber' => '112 Main Street',
            'streetAdditional' => '3nd floor',
            'region' => 'New York',
            'fullName' => 'John Doe',
            'city' => 'New York',
        ];
        $response = $subscription->requestLinkForBillingDetailsUpdate($updatedBilling);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_PATCH,
            self::API_ENDPOINT_URL.'/subscriptions/subscription_123/update-billing',
            [],
            json_encode($updatedBilling)
        );

        $this->assertEquals(self::WEBSITE_ENDPOINT_URL.'/checkout/checkout_dummy_id/update', $response->href);
    }

    /** @test */
    public function can_update_subscription_quantity()
    {
        /** @var Subscription $subscription */
        $subscription = ResourceFactory::createResourceFromApiResult((object) $this->subscriptionDemoData('subscription_123'), new Subscription($this->client));

        $this->httpClient->setSendReturnObjectFromArray($this->subscriptionDemoData('subscription_123'));
        $subscription->update(['quantity' => 2]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_PATCH,
            self::API_ENDPOINT_URL.'/subscriptions/subscription_123',
            [],
            '{"quantity":2}'
        );
    }

    /** @test */
    public function can_update_subscription_with_idempotency_key()
    {
        /** @var Subscription $subscription */
        $subscription = ResourceFactory::createResourceFromApiResult((object) $this->subscriptionDemoData('subscription_123'), new Subscription($this->client));

        $this->httpClient->setSendReturnObjectFromArray($this->subscriptionDemoData('subscription_123'));
        $this->client->subscriptions->update('subscription_123', ['quantity' => 3], [
            'idempotencyKey' => 'my-update-idempotency-key',
        ]);

        $headers = $this->httpClient->lastSentHeaders();
        $this->assertEquals('my-update-idempotency-key', $headers['Idempotency-Key']);
    }

    /** @test */
    public function throws_exception_for_invalid_subscription_id()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->client->subscriptions->get('invalid_subscription_id');
    }

    private function subscriptionDemoData(string $subscriptionId, string $status = SubscriptionStatus::ACTIVE): array
    {
        return [
            'id' => $subscriptionId,
            'resource' => 'subscription',
            'customerId' => 'customer_78b146a7de7d417e9d68d7e6ef193d18',
            'name' => 'Test subscription',
            'description' => 'Test subscription description',
            'startedAt' => '2023-01-11T10:50:50+02:00',
            'endedAt' => null,
            'cancelledAt' => null,
            'renewedAt' => null,
            'renewedUntil' => null,
            'nextRenewalAt' => '2023-02-11T10:50:50+02:00',
            'status' => $status,
            'testmode' => true,
            'quantity' => 1,
            'interval' => 'month',
            'intervalCount' => 1,
            'billingAddress' => [
                'companyName' => 'JOHN DOE INC.',
                'streetAndNumber' => '112 Main Street',
                'streetAdditional' => '3nd floor',
                'postalCode' => '2424 AB',
                'region' => 'New York',
                'fullName' => 'John Doe',
                'city' => 'New York',
                'country' => 'US',
                'vatNumber' => 'US123456789',
                'email' => 'johndoe@example.com',
            ],
            'basePrice' => [
                'value' => '10.00',
                'currency' => 'EUR',
            ],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL . '/subscriptions/' . $subscriptionId,
                    'type' => 'application/hal+json',
                ],
                'customer' => [
                    'href' => self::API_ENDPOINT_URL . '/customers/customer_78b146a7de7d417e9d68d7e6ef193d18',
                    'type' => 'application/hal+json',
                ],
            ],
        ];
    }
}
