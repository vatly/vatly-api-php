<?php

namespace Vatly\API\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\BaseResource;
use Vatly\API\Resources\BaseResourcePage;
use Vatly\API\Resources\Links\PaginationLinks;
use Vatly\API\Resources\ResourceFactory;
use Vatly\API\Resources\SubscriptionPlan;
use Vatly\API\Resources\SubscriptionPlanCollection;

class SubscriptionPlanEndpoint extends BaseEndpoint
{
    protected string $resourcePath = "subscription-plans";

    const RESOURCE_ID_PREFIX = 'subscription_plan_';

    protected function getResourceObject(): SubscriptionPlan
    {
        return new SubscriptionPlan($this->client);
    }

    /**
     * Create a subscription plan. Plans created with a `live_` token start in
     * `pending` status and must be approved by Vatly before use in checkouts;
     * plans created with a `test_` token are auto-approved (`active`).
     *
     * `productType` must be `saas`. The `day` interval is sandbox-only; live
     * plans support `week`, `month`, and `year`.
     *
     * @return SubscriptionPlan|BaseResource
     * @throws ApiException
     */
    public function create(array $payload, array $filters = []): BaseResource
    {
        return $this->rest_create($payload, $filters);
    }

    /**
     * @throws ApiException
     * @return SubscriptionPlan|BaseResource
     */
    public function get(string $id, array $parameters = []): BaseResource
    {
        return $this->rest_read($id, $parameters);
    }

    /**
     * Submit an update to a live subscription plan (PATCH). Each request is the
     * complete set of changes relative to the current live plan. In live mode the
     * change is held as a pending update and reviewed by Vatly before it takes
     * effect (`updateStatus` moves `pending` → `reviewing` → applied); in test mode
     * it is approved automatically. The interval cannot be changed once the plan
     * has ever been used by a subscription; the price stays changeable.
     *
     * @return SubscriptionPlan|BaseResource|null
     * @throws ApiException
     */
    public function update(string $id, array $data = [], array $filters = []): ?BaseResource
    {
        return $this->rest_update($id, $data, $filters);
    }

    /**
     * Archive a subscription plan, closing it to new business
     * (`POST /v1/subscription-plans/{id}/archive`). Subscribers already on the
     * plan keep billing unchanged. The API returns `204 No Content`, so there is
     * nothing to hydrate.
     *
     * @throws ApiException
     */
    public function archive(string $id, array $filters = []): void
    {
        if (empty($id)) {
            throw new ApiException("Invalid resource id.");
        }

        $this->client->performHttpCall(
            self::REST_CREATE,
            "{$this->getResourcePath()}/" . urlencode($id) . "/archive" . $this->buildQueryString($filters),
        );
    }

    /**
     * Re-open an archived plan to new business
     * (`DELETE /v1/subscription-plans/{id}/archive`). Returns the plan, now open
     * to new business again.
     *
     * @return SubscriptionPlan|BaseResource|null
     * @throws ApiException
     */
    public function unarchive(string $id, array $filters = []): ?BaseResource
    {
        if (empty($id)) {
            throw new ApiException("Invalid resource id.");
        }

        $result = $this->client->performHttpCall(
            self::REST_DELETE,
            "{$this->getResourcePath()}/" . urlencode($id) . "/archive" . $this->buildQueryString($filters),
        );

        if ($result === null) {
            return null;
        }

        return ResourceFactory::createResourceFromApiResult($result, $this->getResourceObject());
    }

    /**
     * @return SubscriptionPlanCollection|BaseResourcePage
     * @throws ApiException
     */
    public function page(
        ?string $startingAfter = null,
        ?string $endingBefore = null,
        ?int $limit = null,
        array $parameters = []
    ): BaseResourcePage {
        return $this->rest_list($startingAfter, $endingBefore, $limit, $parameters);
    }

    protected function getResourcePageObject(int $count, PaginationLinks $links): BaseResourcePage
    {
        return new SubscriptionPlanCollection($this->client, $count, $links);
    }
}
