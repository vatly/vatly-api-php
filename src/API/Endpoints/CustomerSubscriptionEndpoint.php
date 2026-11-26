<?php

namespace Vatly\API\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Links\PaginationLinks;
use Vatly\API\Resources\Subscription;
use Vatly\API\Resources\SubscriptionCollection;

/**
 * @extends BaseEndpoint<Subscription, SubscriptionCollection>
 */
class CustomerSubscriptionEndpoint extends BaseEndpoint
{
    protected string $resourcePath = "customers_subscriptions";

    protected function getResourceObject(): Subscription
    {
        return new Subscription($this->client);
    }

    protected function getResourcePageObject(int $count, PaginationLinks $links): SubscriptionCollection
    {
        return new SubscriptionCollection($this->client, $count, $links);
    }

    /**
     * @throws ApiException
     */
    public function getForCustomerId(string $customerId, string $subscriptionId, array $parameters = []): Subscription
    {
        $this->parentId = $customerId;

        return parent::rest_read($subscriptionId, $parameters);
    }

    /**
     * @throws ApiException
     */
    public function pageForCustomerId(
        string $customerId,
        ?string $startingAfter = null,
        ?string $endingBefore = null,
        ?int $limit = null,
        array $parameters = []
    ): SubscriptionCollection {
        $this->parentId = $customerId;

        return parent::rest_list($startingAfter, $endingBefore, $limit, $parameters);
    }
}
