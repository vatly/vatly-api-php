<?php

namespace Vatly\Tests\Endpoints;

use Vatly\API\Resources\Customer;
use Vatly\API\Resources\ResourceFactory;
use Vatly\API\Resources\Subscription;
use Vatly\API\Resources\SubscriptionCollection;
use Vatly\API\VatlyApiClient;

class CustomerSubscriptionsEndpointTest extends BaseEndpointTest
{
    /** @test */
    public function it_can_get_customer_subscriptions(): void
    {
        $customerId = 'customer_78b146a7de7d417e9d68d7e6ef193d18';

        $responseBodyArray = [
            'count' => 2,
            'data' => [
                ['id' => 'subscription_123', 'resource' => 'subscription', 'customer_id' => $customerId],
                ['id' => 'subscription_456', 'resource' => 'subscription', 'customer_id' => $customerId],
            ],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/customers/'.$customerId.'/subscriptions',
                    'type' => 'application/hal+json',
                ],
                'next' => [
                    'href' => self::API_ENDPOINT_URL.'/customers/'.$customerId.'/subscriptions?startingAfter=subscription_next_dummy_id',
                    'type' => 'application/hal+json',
                ],
                'prev' => null,
            ],
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);


        /** @var Customer $customer */
        $customer = $this->getCustomerObject($customerId);

        $result = $customer->subscriptions();

        $this->assertCount(2, $result);
        $this->assertInstanceOf(SubscriptionCollection::class, $result);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_GET,
            self::API_ENDPOINT_URL."/customers/$customerId/subscriptions?",
            [],
            null
        );
    }

    /** @test */
    public function it_can_get_a_specific_customer_subscription(): void
    {
        $customerId = 'customer_78b146a7de7d417e9d68d7e6ef193d18';
        $subscriptionId = 'subscription_123';
        $responseBodyArray = [
            'id' => $subscriptionId,
            'resource' => 'subscription',
            'customer_id' => $customerId,
        ];
        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        /** @var Customer $customer */
        $customer = $this->getCustomerObject($customerId);

        $result = $customer->subscription($subscriptionId);

        $this->assertInstanceOf(Subscription::class, $result);
        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_GET,
            self::API_ENDPOINT_URL."/customers/$customerId/subscriptions/$subscriptionId",
            [],
            null
        );
    }

    private function getCustomerObject(string $customerId)
    {
        $data = (object) [
            'id' => $customerId,
            'resource' => 'customer',
            'testmode' => true,
        ];

        return ResourceFactory::createResourceFromApiResult($data, new Customer($this->client));
    }
}
