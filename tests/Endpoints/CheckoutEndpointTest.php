<?php

declare(strict_types=1);

namespace Vatly\Tests\Endpoints;

use Vatly\API\Resources\Checkout;
use Vatly\API\Resources\CheckoutCollection;
use Vatly\API\Types\CheckoutStatus;
use Vatly\API\VatlyApiClient;

class CheckoutEndpointTest extends BaseEndpointTest
{
    /** @test */
    public function can_create_checkout()
    {
        $responseBodyArray = [
            'id' => "checkout_dummy_id",
            'resource' => 'checkout',
            'merchantId' => 'merchant_123',
            'testmode' => true,
            'redirectUrlSuccess' => 'https://www.sandorian.com/success',
            'redirectUrlCanceled' => 'https://www.sandorian.com/canceled',
            'status' => CheckoutStatus::STATUS_CREATED,
            'metadata' => [
                'order_id' => '123456',
            ],
            'links' => [
                'checkoutUrl' => [
                    'href' => self::WEBSITE_ENDPOINT_URL.'/checkout/checkout_dummy_id',
                    'type' => 'text/html',
                ],
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/checkouts/checkout_dummy_id',
                    'type' => 'application/json',
                ],
            ],
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        $checkout = $this->client->checkouts->create([
            'profileId' => 'profile_123', // TODO check if this is required at this moment
            'products' => [ // list of one-off-product IDs and subscription plan IDs
                [
                    'id' => 'one_off_product_abc_987',
                ],
                [
                    'id' => 'one_off_product_xyz_123',
                    // optional product overrides would go here, i.e. price, quantity
                ],
            ],
            'redirectUrlSuccess' => 'https://www.sandorian.com/success',
            'redirectUrlCanceled' => 'https://www.sandorian.com/canceled',
            'testmode' => true,
            'metadata' => ['order_id' => '123456'], // optional
            'webhookUrls' => [
                'paid' => 'https://your-website.com/webhooks/vatlify/order/123/paid',
                'canceled' => 'https://your-website.com/webhooks/vatlify/order/123/canceled',
                'refundCompleted' => 'https://your-website.com/webhooks/vatlify/order/123/refund-completed',
                'refundCanceled' => 'https://your-website.com/webhooks/vatlify/order/123/refund-canceled',
                'refundFailed' => 'https://your-website.com/webhooks/vatlify/order/123/refund-failed',
            ],
        ], [
            //
        ]);

        $this->assertInstanceOf(Checkout::class, $checkout);
        $this->assertEquals("checkout_dummy_id", $checkout->id);
        $this->assertEquals("merchant_123", $checkout->merchantId);
        $this->assertEquals("checkout", $checkout->resource);
        $this->assertEquals(CheckoutStatus::STATUS_CREATED, $checkout->status);
        $this->assertEquals("https://www.sandorian.com/success", $checkout->redirectUrlSuccess);
        $this->assertEquals("https://www.sandorian.com/canceled", $checkout->redirectUrlCanceled);
        $this->assertTrue($checkout->testmode);
        $this->assertEquals(self::WEBSITE_ENDPOINT_URL.'/checkout/checkout_dummy_id', $checkout->links->checkoutUrl->href);
        $this->assertEquals(self::API_ENDPOINT_URL.'/checkouts/checkout_dummy_id', $checkout->links->self->href);
        $this->assertEquals($responseBodyArray['metadata'], (array) $checkout->metadata);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL."/checkouts",
            [],
            '{
                        "profileId":"profile_123",
                        "products": [
                            {
                                "id": "one_off_product_abc_987"
                            },
                            {
                                "id": "one_off_product_xyz_123"
                            }
                        ],
                        "redirectUrlSuccess":"https://www.sandorian.com/success",
                        "redirectUrlCanceled":"https://www.sandorian.com/canceled",
                        "testmode":true,
                        "metadata": {
                            "order_id": "123456"
                        },
                        "webhookUrls": {
                            "paid": "https://your-website.com/webhooks/vatlify/order/123/paid",
                            "canceled": "https://your-website.com/webhooks/vatlify/order/123/canceled",
                            "refundCompleted": "https://your-website.com/webhooks/vatlify/order/123/refund-completed",
                            "refundCanceled": "https://your-website.com/webhooks/vatlify/order/123/refund-canceled",
                            "refundFailed": "https://your-website.com/webhooks/vatlify/order/123/refund-failed"
                        }
                    }'
        );
    }

    /** @test */
    public function can_get_checkout()
    {
        $responseBodyArray = [
            'id' => "checkout_dummy_id",
            'resource' => 'checkout',
            'merchantId' => 'merchant_123',
            'orderId' => 'order_123',
            'testmode' => true,
            'redirectUrlSuccess' => 'https://www.sandorian.com/success',
            'redirectUrlCanceled' => 'https://www.sandorian.com/canceled',
            'metadata' => [
                'order_id' => '123456',
            ],
            'status' => CheckoutStatus::STATUS_PAID,
            'links' => [
                'checkoutUrl' => [
                    'href' => self::WEBSITE_ENDPOINT_URL.'/checkout/checkout_dummy_id',
                    'type' => 'text/html',
                ],
                'self' => [
                   'href' => self::API_ENDPOINT_URL.'/checkouts/checkout_dummy_id',
                   'type' => 'application/hal+json',
                ],
                'order' => [
                    'href' => self::API_ENDPOINT_URL.'/orders/order_123',
                    'type' => 'application/hal+json',
                ],
            ],
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        $checkout = $this->client->checkouts->get('checkout_dummy_id', []);

        $this->assertInstanceOf(Checkout::class, $checkout);
        $this->assertEquals("checkout_dummy_id", $checkout->id);
        $this->assertEquals("merchant_123", $checkout->merchantId);
        $this->assertEquals("order_123", $checkout->orderId);
        $this->assertEquals("checkout", $checkout->resource);
        $this->assertEquals(CheckoutStatus::STATUS_PAID, $checkout->status);
        $this->assertEquals("https://www.sandorian.com/success", $checkout->redirectUrlSuccess);
        $this->assertEquals("https://www.sandorian.com/canceled", $checkout->redirectUrlCanceled);
        $this->assertTrue($checkout->testmode);
        $this->assertEquals(self::WEBSITE_ENDPOINT_URL.'/checkout/checkout_dummy_id', $checkout->links->checkoutUrl->href);
        $this->assertEquals(self::API_ENDPOINT_URL.'/checkouts/checkout_dummy_id', $checkout->links->self->href);
        $this->assertEquals(self::API_ENDPOINT_URL.'/orders/order_123', $checkout->links->order->href);
        $this->assertEquals($responseBodyArray['metadata'],  (array) $checkout->metadata);
    }

    /** @test */
    public function can_get_checkouts_list()
    {
        $responseBodyArray = [
            'count' => 1,
            'data' => [
                [
                    'id' => "checkout_dummy_id",
                    'resource' => 'checkout',
                    'merchantId' => 'merchant_123',
                    'orderId' => 'order_123',
                    'testmode' => true,
                    'redirectUrlSuccess' => 'https://www.sandorian.com/success',
                    'redirectUrlCanceled' => 'https://www.sandorian.com/canceled',
                    'status' => CheckoutStatus::STATUS_CREATED,
                    'links' => [
                        'checkoutUrl' => [
                            'href' => self::WEBSITE_ENDPOINT_URL.'/checkout/checkout_dummy_id',
                            'type' => 'text/html',
                        ],
                        'self' => [
                            'href' => self::API_ENDPOINT_URL.'/checkouts/checkout_dummy_id',
                            'type' => 'application/hal+json',
                        ],
                    ],
                ],
            ],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/checkouts',
                    'type' => 'application/hal+json',
                ],
                'next' => [
                    'href' => self::API_ENDPOINT_URL.'/checkouts?startingAfter=checkout_next_dummy_id',
                    'type' => 'application/hal+json',
                ],
                'previous' => null,
            ],

        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        $checkoutCollection = $this->client->checkouts->page();

        $this->assertInstanceOf(CheckoutCollection::class, $checkoutCollection);
        $this->assertNull($checkoutCollection->links->previous);
        $this->assertEquals(self::API_ENDPOINT_URL.'/checkouts?startingAfter=checkout_next_dummy_id', $checkoutCollection->links->next->href);
        $this->assertEquals(1, $checkoutCollection->count);

        $checkout = $checkoutCollection[0];
        $this->assertInstanceOf(Checkout::class, $checkout);
        $this->assertEquals("checkout_dummy_id", $checkout->id);
        $this->assertEquals("merchant_123", $checkout->merchantId);
        $this->assertEquals("order_123", $checkout->orderId);
        $this->assertEquals("checkout", $checkout->resource);
        $this->assertEquals(CheckoutStatus::STATUS_CREATED, $checkout->status);
        $this->assertEquals("https://www.sandorian.com/success", $checkout->redirectUrlSuccess);
        $this->assertEquals("https://www.sandorian.com/canceled", $checkout->redirectUrlCanceled);
        $this->assertTrue($checkout->testmode);
        $this->assertEquals(self::WEBSITE_ENDPOINT_URL.'/checkout/checkout_dummy_id', $checkout->links->checkoutUrl->href);
        $this->assertEquals(self::API_ENDPOINT_URL.'/checkouts/checkout_dummy_id', $checkout->links->self->href);
    }

    /** @test */
    public function cat_get_to_next_page(): void
    {
        $responseBodyArrayCollection = [
            [
                'count' => 1,
                'data' => [
                    ['id' => "checkout_dummy_id", 'resource' => 'checkout'],
                ],
                'links' => [
                    'self' => [
                        'href' => self::API_ENDPOINT_URL.'/checkouts',
                        'type' => 'application/hal+json',
                    ],
                    'next' => [
                        'href' => self::API_ENDPOINT_URL.'/checkouts?startingAfter=checkout_next_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'previous' => null,
                ],

            ],
            [
                'count' => 1,
                'data' => [
                    [
                        'id' => "checkout_next_dummy_id",
                        'resource' => 'checkout',
                        'merchantId' => 'merchant_123',
                        'orderId' => 'order_123',
                        'testmode' => true,
                    ],
                ],
                'links' => [
                    'self' => [
                        'href' => self::API_ENDPOINT_URL.'/checkouts?startingAfter=checkout_next_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'next' => null,
                    'previous' => [
                        'href' => self::API_ENDPOINT_URL.'/checkouts',
                        'type' => 'application/hal+json',
                    ],
                ],

            ],
        ];

        $this->httpClient->setSendReturnCollectionFromArray($responseBodyArrayCollection);

        $checkoutCollection = $this->client->checkouts->page();

        /** @var CheckoutCollection $nextCheckoutCollection */
        $nextCheckoutCollection = $checkoutCollection->next();

        $this->assertWasSent(
            VatlyApiClient::HTTP_GET,
            self::API_ENDPOINT_URL.'/checkouts?startingAfter=checkout_next_dummy_id',
            [],
            null
        );

        $checkout = $nextCheckoutCollection[0];

        $this->assertInstanceOf(CheckoutCollection::class, $nextCheckoutCollection);
        $this->assertEquals(self::API_ENDPOINT_URL.'/checkouts', $nextCheckoutCollection->links->previous->href);
        $this->assertNull($nextCheckoutCollection->links->next);
        $this->assertEquals(1, $nextCheckoutCollection->count);

        $this->assertInstanceOf(Checkout::class, $checkout);
        $this->assertEquals("checkout_next_dummy_id", $checkout->id);
        $this->assertEquals("checkout", $checkout->resource);
        $this->assertEquals("merchant_123", $checkout->merchantId);
        $this->assertEquals("order_123", $checkout->orderId);
        $this->assertTrue($checkout->testmode);
    }
}
