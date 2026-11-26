<?php

declare(strict_types=1);

namespace Vatly\API\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\BaseResourcePage;
use Vatly\API\Resources\Links\PaginationLinks;
use Vatly\API\Resources\WebhookEvent;

/**
 * @extends BaseEndpoint<WebhookEvent, BaseResourcePage>
 */
class WebhookEventEndpoint extends BaseEndpoint
{
    protected string $resourcePath = "webhook-events";

    const RESOURCE_ID_PREFIX = 'webhook_event_';

    protected function getResourceObject(): WebhookEvent
    {
        return new WebhookEvent($this->client);
    }

    /**
     * @throws ApiException
     */
    public function get(string $id, array $parameters = []): WebhookEvent
    {
        return $this->rest_read($id, $parameters);
    }

    protected function getResourcePageObject(int $count, PaginationLinks $links): BaseResourcePage
    {
        throw new \LogicException('Webhook events do not support listing.');
    }
}
