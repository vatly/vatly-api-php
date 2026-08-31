<?php

namespace Vatly\Tests\Endpoints;

use Vatly\API\Resources\ResourceFactory;
use Vatly\API\Resources\Subscription;
use Vatly\API\Resources\SubscriptionCollection;
use Vatly\API\Types\Mandate;
use Vatly\API\Types\ScheduledSubscriptionUpdate;
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
        $this->assertEquals('subscription_plan_Wt5mNvBxKw7YcZaEjLhR', $subscription->subscriptionPlanId);
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
        $this->assertNull($subscription->canceledAt);
        $this->assertNull($subscription->renewedAt);
        $this->assertNull($subscription->renewedUntil);
        $this->assertEquals('2023-02-11T10:50:50+02:00', $subscription->nextRenewalAt);
        $this->assertEquals('US', $subscription->billingAddress->country);
        $this->assertEquals('New York', $subscription->billingAddress->city);
        $this->assertInstanceOf(Mandate::class, $subscription->mandate);
        $this->assertEquals('card', $subscription->mandate->method);
        $this->assertEquals('4242', $subscription->mandate->maskedIdentifier);
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
    public function mandate_can_be_null()
    {
        $subscriptionId = 'subscription_no_mandate';
        $responseBodyArray = $this->subscriptionDemoData($subscriptionId);
        $responseBodyArray['mandate'] = null;

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        /** @var Subscription $subscription */
        $subscription = $this->client->subscriptions->get($subscriptionId);

        $this->assertNull($subscription->mandate);
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
    public function can_resume_subscription()
    {
        /** @var Subscription $subscription */
        $subscription = ResourceFactory::createResourceFromApiResult((object) $this->subscriptionDemoData('subscription_123', SubscriptionStatus::ON_GRACE_PERIOD), new Subscription($this->client));

        $this->httpClient->setSendReturnObjectFromArray($this->subscriptionDemoData('subscription_123', SubscriptionStatus::ACTIVE));
        $resumed = $subscription->resume();

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/subscriptions/subscription_123/resume',
            [],
            null
        );

        $this->assertInstanceOf(Subscription::class, $resumed);
        $this->assertEquals(SubscriptionStatus::ACTIVE, $resumed->status);
    }

    /** @test */
    public function can_resume_subscription_with_idempotency_key()
    {
        $this->httpClient->setSendReturnObjectFromArray($this->subscriptionDemoData('subscription_123'));
        $this->client->setIdempotencyKey('my-resume-idempotency-key');
        $this->client->subscriptions->resume('subscription_123');

        $headers = $this->httpClient->lastSentHeaders();
        $this->assertEquals('my-resume-idempotency-key', $headers['Idempotency-Key']);
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
        $response = $subscription->updateBilling($updatedBilling);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/subscriptions/subscription_123/billing-update-link',
            [],
            json_encode($updatedBilling)
        );

        $this->assertEquals(self::WEBSITE_ENDPOINT_URL.'/checkout/checkout_dummy_id/update', $response->href);
    }

    /** @test */
    public function update_billing_punycodes_email_domains_in_billing_address(): void
    {
        $this->httpClient->setSendReturnObjectFromArray([
            'href' => self::WEBSITE_ENDPOINT_URL.'/checkout/checkout_dummy_id/update',
            'type' => 'text/html',
        ]);

        $this->client->subscriptions->updateBilling('subscription_123', [
            'billingAddress' => [
                'email' => 'billing@müller.de',
                'city' => 'München',
            ],
        ]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/subscriptions/subscription_123/billing-update-link',
            [],
            '{"billingAddress":{"email":"billing@xn--mller-kva.de","city":"München"}}'
        );
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
    public function can_update_subscription_price_with_a_plan_switch()
    {
        $this->httpClient->setSendReturnObjectFromArray($this->subscriptionDemoData('subscription_123'));

        $this->client->subscriptions->update('subscription_123', [
            'subscriptionPlanId' => 'subscription_plan_Wt5mNvBxKw7YcZaEjLhR',
            'price' => ['value' => '99.99', 'currency' => 'EUR'],
            'quantity' => 5,
        ]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_PATCH,
            self::API_ENDPOINT_URL.'/subscriptions/subscription_123',
            [],
            '{"subscriptionPlanId":"subscription_plan_Wt5mNvBxKw7YcZaEjLhR","price":{"value":"99.99","currency":"EUR"},"quantity":5}'
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

    /** @test */
    public function it_hydrates_scheduled_update_into_the_typed_object()
    {
        $subscriptionId = 'subscription_78b146a7de7d417e9d68d7e6ef193d18';

        $data = $this->subscriptionDemoData($subscriptionId);
        $data['scheduledUpdate'] = [
            'subscriptionPlanId' => 'subscription_plan_next',
            'name' => 'Pro Annual',
            'description' => 'Billed yearly',
            'basePrice' => ['value' => '990.00', 'currency' => 'EUR'],
            'quantity' => 2,
            'interval' => 'year',
            'intervalCount' => 1,
            'effectiveAt' => '2024-03-15T10:30:00Z',
        ];

        $this->httpClient->setSendReturnObjectFromArray($data);

        /** @var Subscription $subscription */
        $subscription = $this->client->subscriptions->get($subscriptionId);

        $this->assertInstanceOf(ScheduledSubscriptionUpdate::class, $subscription->scheduledUpdate);
        $this->assertSame('subscription_plan_next', $subscription->scheduledUpdate->subscriptionPlanId);
        $this->assertSame('Pro Annual', $subscription->scheduledUpdate->name);
        $this->assertSame('Billed yearly', $subscription->scheduledUpdate->description);
        $this->assertSame('990.00', $subscription->scheduledUpdate->basePrice->value);
        $this->assertSame('EUR', $subscription->scheduledUpdate->basePrice->currency);
        $this->assertSame(2, $subscription->scheduledUpdate->quantity);
        $this->assertSame('year', $subscription->scheduledUpdate->interval);
        $this->assertSame(1, $subscription->scheduledUpdate->intervalCount);
        $this->assertSame('2024-03-15T10:30:00Z', $subscription->scheduledUpdate->effectiveAt);
    }

    /** @test */
    public function it_exposes_null_scheduled_update_when_none_is_pending()
    {
        $subscriptionId = 'subscription_78b146a7de7d417e9d68d7e6ef193d18';

        $data = $this->subscriptionDemoData($subscriptionId);
        $data['scheduledUpdate'] = null;

        $this->httpClient->setSendReturnObjectFromArray($data);

        /** @var Subscription $subscription */
        $subscription = $this->client->subscriptions->get($subscriptionId);

        $this->assertNull($subscription->scheduledUpdate);
    }

    /** @test */
    public function it_hydrates_scheduled_update_with_null_effective_at()
    {
        $subscriptionId = 'subscription_78b146a7de7d417e9d68d7e6ef193d18';

        $data = $this->subscriptionDemoData($subscriptionId);
        $data['scheduledUpdate'] = [
            'subscriptionPlanId' => 'subscription_plan_next',
            'name' => 'Pro Annual',
            'description' => 'Billed yearly',
            'basePrice' => ['value' => '990.00', 'currency' => 'EUR'],
            'quantity' => 2,
            'interval' => 'year',
            'intervalCount' => 1,
            'effectiveAt' => null,
        ];

        $this->httpClient->setSendReturnObjectFromArray($data);

        /** @var Subscription $subscription */
        $subscription = $this->client->subscriptions->get($subscriptionId);

        $this->assertInstanceOf(ScheduledSubscriptionUpdate::class, $subscription->scheduledUpdate);
        $this->assertNull($subscription->scheduledUpdate->effectiveAt);
    }

    private function subscriptionDemoData(string $subscriptionId, string $status = SubscriptionStatus::ACTIVE): array
    {
        return [
            'id' => $subscriptionId,
            'resource' => 'subscription',
            'customerId' => 'customer_78b146a7de7d417e9d68d7e6ef193d18',
            'subscriptionPlanId' => 'subscription_plan_Wt5mNvBxKw7YcZaEjLhR',
            'name' => 'Test subscription',
            'description' => 'Test subscription description',
            'startedAt' => '2023-01-11T10:50:50+02:00',
            'endedAt' => null,
            'canceledAt' => null,
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
                'taxId' => 'US123456789',
                'email' => 'johndoe@example.com',
            ],
            'basePrice' => [
                'value' => '10.00',
                'currency' => 'EUR',
            ],
            'mandate' => [
                'method' => 'card',
                'maskedIdentifier' => '4242',
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
