<?php

namespace Vatly\API\Resources;

class WebhookEndpointCollection extends BaseResourcePage
{
    public function getCollectionResourceName(): ?string
    {
        return 'webhook-endpoints';
    }

    protected function createResourceObject(): WebhookEndpoint
    {
        return new WebhookEndpoint($this->apiClient);
    }
}
