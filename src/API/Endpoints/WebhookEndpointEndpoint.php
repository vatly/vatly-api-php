<?php

declare(strict_types=1);

namespace Vatly\API\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\BaseResource;
use Vatly\API\Resources\BaseResourcePage;
use Vatly\API\Resources\Links\PaginationLinks;
use Vatly\API\Resources\WebhookEndpoint;
use Vatly\API\Resources\WebhookEndpointCollection;

class WebhookEndpointEndpoint extends BaseEndpoint
{
    protected string $resourcePath = "webhook-endpoints";

    const RESOURCE_ID_PREFIX = 'webhook_';

    protected function getResourceObject(): WebhookEndpoint
    {
        return new WebhookEndpoint($this->client);
    }

    /**
     * Register a webhook endpoint. Supply `url` and a write-only signing
     * `secret` (min 10 chars); the secret is never returned, so keep the value
     * you send. There is at most one endpoint per mode.
     *
     * @return WebhookEndpoint|BaseResource
     * @throws ApiException
     */
    public function create(array $payload, array $filters = []): BaseResource
    {
        return $this->rest_create($payload, $filters);
    }

    /**
     * @return WebhookEndpoint|BaseResource
     * @throws ApiException
     */
    public function get(string $id, array $parameters = []): BaseResource
    {
        return $this->rest_read($id, $parameters);
    }

    /**
     * Update an endpoint's `url`, its signing `secret`, or both. Sending an
     * empty body is a no-op that returns the current endpoint.
     *
     * @return WebhookEndpoint|BaseResource|null
     * @throws ApiException
     */
    public function update(string $id, array $data = [], array $filters = []): ?BaseResource
    {
        return $this->rest_update($id, $data, $filters);
    }

    /**
     * Delete an endpoint. Vatly stops sending deliveries to it immediately.
     *
     * @throws ApiException
     */
    public function delete(string $id): void
    {
        $this->rest_delete($id);
    }

    /**
     * List the webhook endpoints for the token's mode. Because there is at most
     * one endpoint per mode, this returns at most one endpoint.
     *
     * @return WebhookEndpointCollection|BaseResourcePage
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
        return new WebhookEndpointCollection($this->client, $count, $links);
    }
}
