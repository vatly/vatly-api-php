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
     * Fast-forward a subscription renewal for testing purposes.
     *
     * @throws \Vatly\API\Exceptions\ApiException
     */
    public function fastForwardSubscriptionRenewal(string $subscriptionId): ?object
    {
        return $this->client->performHttpCall(
            VatlyApiClient::HTTP_POST,
            "test-helpers/subscriptions/" . urlencode($subscriptionId) . "/fast-forward-renewal",
        );
    }
}
