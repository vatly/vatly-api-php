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

    /**
     * The endpoint's persisted subscription set: the public event names it
     * receives. Current names are delivered; a retired public name may remain
     * for stable readback but is inert. New event names are never added
     * automatically — update the endpoint to opt in. An empty array means no
     * domain events are delivered (a dormant endpoint), although `webhook.setup`
     * is still sent whenever Vatly verifies the endpoint configuration.
     *
     * @var string[]
     */
    public array $enabledEvents = [];

    public ?string $createdAt = null;

    public WebhookEndpointLinks $links;

    /**
     * Update this endpoint's `url`, its signing `secret`, and/or its
     * `enabledEvents` subscription set (a full-set replacement). The secret is
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
