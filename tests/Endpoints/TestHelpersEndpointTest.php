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
    public function can_simulate_payment_failure(): void
    {
        $transactionId = 'transaction_78b146a7de7d417e9d68d7e6ef193d18';

        $this->httpClient->setSendReturnObjectFromArray([
            'id' => $transactionId,
            'resource' => 'transaction',
        ]);

        $this->client->testHelpers->simulatePaymentFailure($transactionId);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/test-helpers/mandated-payments/'.$transactionId.'/simulate-failure',
            [],
            null
        );
    }

    /** @test */
    public function can_simulate_payment_failure_with_data(): void
    {
        $transactionId = 'transaction_78b146a7de7d417e9d68d7e6ef193d18';

        $this->httpClient->setSendReturnObjectFromArray([
            'id' => $transactionId,
            'resource' => 'transaction',
        ]);

        $data = ['reason' => 'insufficient_funds'];
        $this->client->testHelpers->simulatePaymentFailure($transactionId, $data);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/test-helpers/mandated-payments/'.$transactionId.'/simulate-failure',
            [],
            '{"reason":"insufficient_funds"}'
        );
    }
}
