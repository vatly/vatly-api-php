<?php

namespace Vatly\Tests\Endpoints;

use Vatly\API\Resources\SubscriptionPlan;
use Vatly\API\Resources\SubscriptionPlanCollection;

class SubscriptionPlanEndpointTest extends BaseEndpointTest
{
    /** @test */
    public function can_get_subscription_plan()
    {
        $productId = 'subscription_plan_78b146a7de7d417e9d68d7e6ef193d18';

        $responseBodyArray = [
            'id' => $productId,
            'resource' => 'subscription_plan',
            'name' => 'Test product',
            'description' => 'Test product description',
            'basePrice' => [
                'value' => '10.00',
                'currency' => 'EUR',
            ],
            'interval' => 'month',
            'intervalCount' => 1,
            'testmode' => false,
            'status' => 'active',
            'createdAt' => '2023-01-11T10:50:50+02:00',
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL. '/subscription-plans/' . $productId,
                    'type' => 'application/hal+json',
                ],
            ],
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        /** @var SubscriptionPlan $product */
        $product = $this->client->subscriptionPlans->get($productId);

        $this->assertEquals($productId, $product->id);
        $this->assertEquals('subscription_plan', $product->resource);
        $this->assertEquals('Test product', $product->name);
        $this->assertEquals('Test product description', $product->description);
        $this->assertEquals('10.00', $product->basePrice->value);
        $this->assertEquals('EUR', $product->basePrice->currency);
        $this->assertEquals('month', $product->interval);
        $this->assertEquals(1, $product->intervalCount);
        $this->assertFalse($product->testmode);
        $this->assertEquals('active', $product->status);
        $this->assertEquals('2023-01-11T10:50:50+02:00', $product->createdAt);

        $this->assertEquals(self::API_ENDPOINT_URL. '/subscription-plans/' . $productId, $product->links->self->href);
        $this->assertEquals('application/hal+json', $product->links->self->type);
    }

    /** @test */
    public function can_list_subscription_plans()
    {
        $responseBodyArray = [
            'count' => 2,
            'data' => [
                ['id' => 'subscription_plan_123', 'resource' => 'subscription_plan'],
                ['id' => 'subscription_plan_456', 'resource' => 'subscription_plan'],
            ],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/subscription-plans',
                    'type' => 'application/hal+json',
                ],
                'next' => [
                    'href' => self::API_ENDPOINT_URL.'/subscription-plans?startingAfter=subscription_plan_next_dummy_id',
                    'type' => 'application/hal+json',
                ],
                'previous' => [
                    'href' => self::API_ENDPOINT_URL.'/subscription-plans?endingBefore=subscription_plan_previous_dummy_id',
                    'type' => 'application/hal+json',
                ],
            ],
        ];


        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        $productCollection = $this->client->subscriptionPlans->page();

        $this->assertEquals(2, $productCollection->count);
        $this->assertCount(2, $productCollection);
        $this->assertInstanceOf(SubscriptionPlanCollection::class, $productCollection);
        $this->assertInstanceOf(SubscriptionPlan::class, $productCollection[0]);
        $this->assertInstanceOf(SubscriptionPlan::class, $productCollection[1]);

        $this->assertEquals('subscription_plan_123', $productCollection[0]->id);
        $this->assertEquals('subscription_plan_456', $productCollection[1]->id);

        $this->assertEquals(self::API_ENDPOINT_URL.'/subscription-plans', $productCollection->links->self->href);
        $this->assertEquals('application/hal+json', $productCollection->links->self->type);
        $this->assertEquals(self::API_ENDPOINT_URL.'/subscription-plans?startingAfter=subscription_plan_next_dummy_id', $productCollection->links->next->href);
        $this->assertEquals('application/hal+json', $productCollection->links->next->type);
        $this->assertEquals(self::API_ENDPOINT_URL.'/subscription-plans?endingBefore=subscription_plan_previous_dummy_id', $productCollection->links->previous->href);
        $this->assertEquals('application/hal+json', $productCollection->links->previous->type);
    }

    /** @test */
    public function can_get_next_page_of_subscription_plans()
    {
        $responseBodyArrayCollection = [
            [
                'count' => 2,
                'data' => [
                    ['id' => 'subscription_plan_123', 'resource' => 'subscription_plan',],
                    ['id' => 'subscription_plan_456', 'resource' => 'subscription_plan',],
                ],
                'links' => [
                    'self' => [
                        'href' => self::API_ENDPOINT_URL . '/subscription-plans',
                        'type' => 'application/hal+json',
                    ],
                    'next' => [
                        'href' => self::API_ENDPOINT_URL . '/subscription-plans?startingAfter=subscription_plan_next_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'previous' => null,
                ],
            ],
            [
                'count' => 1,
                'data' => [
                    ['id' => 'subscription_plan_789', 'resource' => 'subscription_plan',],
                ],
                'links' => [
                    'self' => [
                        'href' => self::API_ENDPOINT_URL . '/subscription-plans?startingAfter=subscription_plan_next_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'next' => null,
                    'previous' => [
                        'href' => self::API_ENDPOINT_URL . '/subscription-plans',
                        'type' => 'application/hal+json',
                    ],
                ],
            ],
        ];

        $this->httpClient->setSendReturnCollectionFromArray($responseBodyArrayCollection);

        $productCollection = $this->client->subscriptionPlans->page();

        $nextProductCollection = $productCollection->next();

        $this->assertEquals(1, $nextProductCollection->count);
        $this->assertCount(1, $nextProductCollection);
        $this->assertInstanceOf(SubscriptionPlanCollection::class, $nextProductCollection);

        $product = $nextProductCollection[0];
        $this->assertInstanceOf(SubscriptionPlan::class, $product);
        $this->assertEquals('subscription_plan_789', $product->id);

        $this->assertNull($nextProductCollection->next());
    }
}
