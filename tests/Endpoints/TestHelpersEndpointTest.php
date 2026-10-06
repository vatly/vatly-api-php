<?php

namespace Vatly\Tests\Endpoints;

use Vatly\API\VatlyApiClient;

class TestHelpersEndpointTest extends BaseEndpointTest
{
    /** @test */
    public function can_fast_forward_subscription_renewal(): void
    {
        $subscriptionId = 'subscription_78b146a7de7d417e9d68d7e6ef193d18';

        $this->httpClient->setSendReturnObjectFromArray([
            'id' => $subscriptionId,
            'resource' => 'subscription',
        ]);

        $this->client->testHelpers->fastForwardSubscriptionRenewal($subscriptionId);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/test-helpers/subscriptions/'.$subscriptionId.'/fast-forward-renewal',
            [],
            null
        );
    }

    /** @test */
    public function can_fast_forward_subscription_renewal_forcing_a_failed_payment(): void
    {
        $subscriptionId = 'subscription_78b146a7de7d417e9d68d7e6ef193d18';

        $this->httpClient->setSendReturnObjectFromArray([
            'id' => $subscriptionId,
            'resource' => 'subscription',
        ]);

        $this->client->testHelpers->fastForwardSubscriptionRenewal($subscriptionId, [
            'paymentStatus' => 'failed',
            'failureReason' => 'card_expired',
        ]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/test-helpers/subscriptions/'.$subscriptionId.'/fast-forward-renewal',
            [],
            '{"paymentStatus":"failed","failureReason":"card_expired"}'
        );
    }

    /** @test */
    public function can_simulate_an_order_payment(): void
    {
        $orderId = 'order_Hn5xWqVfKm8RjTgYbUcP';

        $this->httpClient->setSendReturnObjectFromArray([
            'id' => $orderId,
            'resource' => 'order',
        ]);

        $this->client->testHelpers->simulateOrderPayment($orderId, [
            'paymentStatus' => 'paid',
        ]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/test-helpers/orders/'.$orderId.'/simulate-payment',
            [],
            '{"paymentStatus":"paid"}'
        );
    }

    /** @test */
    public function can_simulate_an_order_payment_forcing_a_hard_decline(): void
    {
        $orderId = 'order_Hn5xWqVfKm8RjTgYbUcP';

        $this->httpClient->setSendReturnObjectFromArray([
            'id' => $orderId,
            'resource' => 'order',
        ]);

        $this->client->testHelpers->simulateOrderPayment($orderId, [
            'paymentStatus' => 'failed',
            'failureReason' => 'card_expired',
        ]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/test-helpers/orders/'.$orderId.'/simulate-payment',
            [],
            '{"paymentStatus":"failed","failureReason":"card_expired"}'
        );
    }
}
