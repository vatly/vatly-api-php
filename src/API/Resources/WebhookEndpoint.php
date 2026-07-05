<?php

namespace Vatly\API\Resources;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Links\WebhookEndpointLinks;

class WebhookEndpoint extends BaseResource
{
    /**
     * @example webhook_QdEpFhdSrG4Y3DnfsdqsH
     */
    public string $id;

    /**
     * @example webhook_endpoint
     */
    public string $resource;

    public bool $testmode;

    /**
     * The HTTPS URL deliveries are POSTed to.
     */
    public string $url;

    public ?string $createdAt = null;

    public WebhookEndpointLinks $links;

    /**
     * Update this endpoint's `url`, its signing `secret`, or both. The secret is
     * write-only and never returned.
     *
     * @return WebhookEndpoint|BaseResource|null
     * @throws ApiException
     */
    public function update(array $data = []): ?BaseResource
    {
        return $this->apiClient->webhookEndpoints->update($this->id, $data);
    }

    /**
     * Delete this endpoint. Vatly stops sending deliveries to it immediately.
     *
     * @throws ApiException
     */
    public function delete(): void
    {
        $this->apiClient->webhookEndpoints->delete($this->id);
    }
}
