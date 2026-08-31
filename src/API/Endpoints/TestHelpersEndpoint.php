<?php

declare(strict_types=1);

namespace Vatly\API\Endpoints;

use Vatly\API\VatlyApiClient;

class TestHelpersEndpoint
{
    protected VatlyApiClient $client;

    public function __construct(VatlyApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Fast-forward a subscription renewal for testing purposes (test mode only).
     *
     * Pass an optional `$body` to force the outcome of the renewal payment:
     *  - `[]` (default) advances the billing cycle and leaves the payment pending.
     *  - `['paymentStatus' => 'paid']` settles the renewal payment.
     *  - `['paymentStatus' => 'failed']` declines it and starts a payment recovery,
     *    so `order.payment_failed` is delivered to your webhook endpoint. Add a
     *    `failureReason` (e.g. `'card_expired'`) to pick which decline to simulate:
     *    a soft decline (`insufficient_funds`, `temporary_decline`,
     *    `general_failure`) retries over weeks; any other value is a hard decline
     *    that drives the customer to supply a new payment method.
     *
     * @param array<string, mixed> $body
     * @throws \Vatly\API\Exceptions\ApiException
     */
    public function fastForwardSubscriptionRenewal(string $subscriptionId, array $body = []): ?object
    {
        return $this->client->performHttpCall(
            VatlyApiClient::HTTP_POST,
            "test-helpers/subscriptions/" . urlencode($subscriptionId) . "/fast-forward-renewal",
            empty($body) ? null : json_encode($body),
        );
    }
}
