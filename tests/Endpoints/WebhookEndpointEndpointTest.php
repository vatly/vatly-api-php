<?php

declare(strict_types=1);

namespace Vatly\Tests\Endpoints;

use Vatly\API\Resources\WebhookEndpoint;
use Vatly\API\Resources\WebhookEndpointCollection;
use Vatly\API\VatlyApiClient;

class WebhookEndpointEndpointTest extends BaseEndpointTest
{
    private const WEBHOOK_ENDPOINT_ID = 'webhook_QdEpFhdSrG4Y3DnfsdqsH';

    /** @test */
    public function it_can_register_a_webhook_endpoint(): void
    {
        $this->httpClient->setSendReturnObjectFromArray($this->demoData());

        /** @var WebhookEndpoint $endpoint */
        $endpoint = $this->client->webhookEndpoints->create([
            'url' => 'https://merchant.example/webhooks/vatly',
            'secret' => 'whsec_3f9a1c7e2d4f7b9c5a2c1d5b7e9f3a8d',
        ]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/webhook-endpoints',
            [],
            '{"url":"https:\/\/merchant.example\/webhooks\/vatly","secret":"whsec_3f9a1c7e2d4f7b9c5a2c1d5b7e9f3a8d"}'
        );

        $this->assertInstanceOf(WebhookEndpoint::class, $endpoint);
        $this->assertEquals(self::WEBHOOK_ENDPOINT_ID, $endpoint->id);
        $this->assertEquals('webhook_endpoint', $endpoint->resource);
        $this->assertFalse($endpoint->testmode);
        $this->assertEquals('https://merchant.example/webhooks/vatly', $endpoint->url);
        $this->assertEquals('2024-01-15T10:30:00Z', $endpoint->createdAt);
        $this->assertEquals(self::API_ENDPOINT_URL.'/webhook-endpoints/'.self::WEBHOOK_ENDPOINT_ID, $endpoint->links->self->href);

        // The write-only signing secret is never returned on the resource.
        $this->assertFalse(property_exists($endpoint, 'secret') && isset($endpoint->secret));
    }

    /** @test */
    public function it_can_get_a_webhook_endpoint(): void
    {
        $this->httpClient->setSendReturnObjectFromArray($this->demoData());

        /** @var WebhookEndpoint $endpoint */
        $endpoint = $this->client->webhookEndpoints->get(self::WEBHOOK_ENDPOINT_ID);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_GET,
            self::API_ENDPOINT_URL.'/webhook-endpoints/'.self::WEBHOOK_ENDPOINT_ID,
            [],
            null
        );

        $this->assertInstanceOf(WebhookEndpoint::class, $endpoint);
        $this->assertEquals(self::WEBHOOK_ENDPOINT_ID, $endpoint->id);
        $this->assertEquals('https://merchant.example/webhooks/vatly', $endpoint->url);
    }

    /** @test */
    public function it_can_list_webhook_endpoints(): void
    {
        $this->httpClient->setSendReturnObjectFromArray([
            'count' => 1,
            'data' => [$this->demoData()],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/webhook-endpoints',
                    'type' => 'application/json',
                ],
                'next' => null,
                'prev' => null,
            ],
        ]);

        /** @var WebhookEndpointCollection $endpoints */
        $endpoints = $this->client->webhookEndpoints->page();

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_GET,
            self::API_ENDPOINT_URL.'/webhook-endpoints?',
            [],
            null
        );

        $this->assertInstanceOf(WebhookEndpointCollection::class, $endpoints);
        $this->assertEquals(1, $endpoints->count);
        $this->assertInstanceOf(WebhookEndpoint::class, $endpoints[0]);
        $this->assertEquals(self::WEBHOOK_ENDPOINT_ID, $endpoints[0]->id);
    }

    /** @test */
    public function it_can_update_a_webhook_endpoint(): void
    {
        $this->httpClient->setSendReturnObjectFromArray(
            $this->demoData('https://merchant.example/webhooks/vatly-v2')
        );

        /** @var WebhookEndpoint $endpoint */
        $endpoint = $this->client->webhookEndpoints->update(self::WEBHOOK_ENDPOINT_ID, [
            'url' => 'https://merchant.example/webhooks/vatly-v2',
        ]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_PATCH,
            self::API_ENDPOINT_URL.'/webhook-endpoints/'.self::WEBHOOK_ENDPOINT_ID,
            [],
            '{"url":"https:\/\/merchant.example\/webhooks\/vatly-v2"}'
        );

        $this->assertEquals('https://merchant.example/webhooks/vatly-v2', $endpoint->url);
    }

    /** @test */
    public function it_can_update_a_webhook_endpoint_secret_from_a_resource_instance(): void
    {
        $endpoint = new WebhookEndpoint($this->client);
        $endpoint->id = self::WEBHOOK_ENDPOINT_ID;

        $this->httpClient->setSendReturnObjectFromArray($this->demoData());

        $endpoint->update(['secret' => 'whsec_a1b2c3d4e5f60718293a4b5c6d7e8f90']);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_PATCH,
            self::API_ENDPOINT_URL.'/webhook-endpoints/'.self::WEBHOOK_ENDPOINT_ID,
            [],
            '{"secret":"whsec_a1b2c3d4e5f60718293a4b5c6d7e8f90"}'
        );
    }

    /** @test */
    public function it_can_delete_a_webhook_endpoint(): void
    {
        // DELETE responds 204 No Content — the spy returns null.
        $this->httpClient->setSendReturnNull();

        $this->client->webhookEndpoints->delete(self::WEBHOOK_ENDPOINT_ID);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_DELETE,
            self::API_ENDPOINT_URL.'/webhook-endpoints/'.self::WEBHOOK_ENDPOINT_ID,
            [],
            null
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function demoData(string $url = 'https://merchant.example/webhooks/vatly'): array
    {
        return [
            'id' => self::WEBHOOK_ENDPOINT_ID,
            'resource' => 'webhook_endpoint',
            'testmode' => false,
            'url' => $url,
            'createdAt' => '2024-01-15T10:30:00Z',
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/webhook-endpoints/'.self::WEBHOOK_ENDPOINT_ID,
                    'type' => 'application/json',
                ],
            ],
        ];
    }
}
