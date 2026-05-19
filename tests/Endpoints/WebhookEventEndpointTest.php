<?php

declare(strict_types=1);

namespace Vatly\Tests\Endpoints;

use Vatly\API\Resources\WebhookEvent;
use Vatly\API\Types\WebhookEvent as WebhookEventType;
use Vatly\API\VatlyApiClient;

class WebhookEventEndpointTest extends BaseEndpointTest
{
    /** @test */
    public function can_get_webhook_event(): void
    {
        $webhookEventId = 'webhook_event_Qk8pRtSvWm2NjLhYcZaE';
        $orderId = 'order_Hn5xWqVfKm8RjTgYbUcP';

        $responseBodyArray = [
            'id' => $webhookEventId,
            'resource' => 'webhook_event',
            'eventName' => WebhookEventType::ORDER_PAID,
            'entityType' => 'order',
            'entityId' => $orderId,
            'object' => [
                'id' => $orderId,
                'resource' => 'order',
                'testmode' => false,
                'status' => 'paid',
                'total' => ['value' => '29.99', 'currency' => 'EUR'],
                'subtotal' => ['value' => '24.79', 'currency' => 'EUR'],
            ],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/webhook-events/'.$webhookEventId,
                    'type' => 'application/json',
                ],
            ],
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        /** @var WebhookEvent $event */
        $event = $this->client->webhookEvents->get($webhookEventId);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_GET,
            self::API_ENDPOINT_URL.'/webhook-events/'.$webhookEventId,
            [],
            null
        );

        $this->assertInstanceOf(WebhookEvent::class, $event);
        $this->assertEquals($webhookEventId, $event->id);
        $this->assertEquals('webhook_event', $event->resource);
        $this->assertEquals(WebhookEventType::ORDER_PAID, $event->eventName);
        $this->assertEquals('order', $event->entityType);
        $this->assertEquals($orderId, $event->entityId);
        $this->assertIsObject($event->object);
        $this->assertEquals($orderId, $event->object->id);
        $this->assertEquals('paid', $event->object->status);

        $this->assertEquals(self::API_ENDPOINT_URL.'/webhook-events/'.$webhookEventId, $event->links->self->href);
        $this->assertEquals('application/json', $event->links->self->type);
    }
}
