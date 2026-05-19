<?php

declare(strict_types=1);

namespace Vatly\API\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\BaseResource;
use Vatly\API\Resources\BaseResourcePage;
use Vatly\API\Resources\Links\PaginationLinks;
use Vatly\API\Resources\WebhookEvent;

class WebhookEventEndpoint extends BaseEndpoint
{
    protected string $resourcePath = "webhook-events";

    const RESOURCE_ID_PREFIX = 'webhook_event_';

    protected function getResourceObject(): WebhookEvent
    {
        return new WebhookEvent($this->client);
    }

    /**
     * @return WebhookEvent|BaseResource
     * @throws ApiException
     */
    public function get(string $id, array $parameters = []): BaseResource
    {
        return $this->rest_read($id, $parameters);
    }

    protected function getResourcePageObject(int $count, PaginationLinks $links): BaseResourcePage
    {
        throw new \LogicException('Webhook events do not support listing.');
    }
}
