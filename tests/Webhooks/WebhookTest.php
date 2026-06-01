<?php

declare(strict_types=1);

namespace Vatly\Tests\Webhooks;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Vatly\API\Exceptions\InvalidSignatureException;
use Vatly\API\Types\WebhookEventName;
use Vatly\API\Webhooks\Webhook;
use Vatly\API\Webhooks\WebhookPayload;
use Vatly\API\Webhooks\WebhookSetupCallPayload;

class WebhookTest extends TestCase
{
    private string $secret = 'test_webhook_secret';

    private function makePayload(array $overrides = []): string
    {
        $data = array_merge([
            'id' => 'webhook_event_Qk8pRtSvWm2NjLhYcZaE',
            'resource' => 'webhook_event',
            'eventName' => 'order.paid',
            'entityType' => 'order',
            'entityId' => 'order_Hn5xWqVfKm8RjTgYbUcP',
            'object' => ['id' => 'order_Hn5xWqVfKm8RjTgYbUcP', 'resource' => 'order'],
            'createdAt' => '2023-01-11T10:50:50+02:00',
            'testmode' => true,
        ], $overrides);

        return json_encode($data);
    }

    private function sign(string $payload, ?string $secret = null, ?int $timestamp = null): string
    {
        $secret ??= $this->secret;
        $timestamp ??= time();
        $v1 = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return "t={$timestamp},v1={$v1}";
    }

    public function test_it_parses_a_valid_webhook(): void
    {
        $payload = $this->makePayload();
        $signature = $this->sign($payload);

        $event = Webhook::parse($payload, $signature, $this->secret);

        $this->assertInstanceOf(WebhookPayload::class, $event);
        $this->assertSame('webhook_event_Qk8pRtSvWm2NjLhYcZaE', $event->id);
        $this->assertSame('webhook_event', $event->resource);
        $this->assertSame('order.paid', $event->eventName);
        $this->assertSame('order', $event->entityType);
        $this->assertSame('order_Hn5xWqVfKm8RjTgYbUcP', $event->entityId);
        $this->assertIsObject($event->object);
        $this->assertSame('order_Hn5xWqVfKm8RjTgYbUcP', $event->object->id);
        $this->assertSame('order', $event->object->resource);
        $this->assertTrue($event->testmode);
        $this->assertSame('2023-01-11T10:50:50+02:00', $event->createdAt);
    }

    public function test_it_parses_a_webhook_without_object(): void
    {
        $data = [
            'id' => 'webhook_event_Qk8pRtSvWm2NjLhYcZaE',
            'resource' => 'webhook_event',
            'eventName' => 'order.paid',
            'entityType' => 'order',
            'entityId' => 'order_Hn5xWqVfKm8RjTgYbUcP',
            'createdAt' => '2023-01-11T10:50:50+02:00',
            'testmode' => true,
        ];
        $payload = json_encode($data);
        $signature = $this->sign($payload);

        $event = Webhook::parse($payload, $signature, $this->secret);

        $this->assertNull($event->object);
    }

    public function test_it_parses_the_webhook_setup_call(): void
    {
        $payload = $this->makePayload([
            'id' => 'webhook_event_St9uVwXyZa1BcDeFgHiJ',
            'eventName' => WebhookEventName::WEBHOOK_SETUP,
            'entityType' => 'webhook',
            'entityId' => 'webhook_QdEpFhdSrG4Y3DnfsdqsH',
            'object' => [
                'resource' => 'webhook',
                'id' => 'webhook_QdEpFhdSrG4Y3DnfsdqsH',
                'url' => 'https://merchant.example/webhooks/vatly',
                'testmode' => true,
            ],
        ]);
        $signature = $this->sign($payload);

        $event = Webhook::parse($payload, $signature, $this->secret);

        // The setup call is delivered as a standard envelope; parse() returns a
        // WebhookSetupCallPayload (a WebhookPayload subclass) so receivers can
        // instanceof-detect and 2xx-acknowledge it.
        $this->assertInstanceOf(WebhookSetupCallPayload::class, $event);
        $this->assertInstanceOf(WebhookPayload::class, $event);
        $this->assertSame(WebhookEventName::WEBHOOK_SETUP, $event->eventName);
        $this->assertSame('webhook', $event->entityType);
        $this->assertSame('webhook_QdEpFhdSrG4Y3DnfsdqsH', $event->entityId);
        $this->assertSame('webhook_event', $event->resource);
        $this->assertSame('webhook_event_St9uVwXyZa1BcDeFgHiJ', $event->id);
        $this->assertIsObject($event->object);
        $this->assertSame('webhook', $event->object->resource);
    }

    public function test_it_throws_for_invalid_signature(): void
    {
        $this->expectException(InvalidSignatureException::class);

        $payload = $this->makePayload();
        Webhook::parse($payload, 'bad_signature', $this->secret);
    }

    public function test_it_throws_for_wrong_secret(): void
    {
        $this->expectException(InvalidSignatureException::class);

        $payload = $this->makePayload();
        $signature = $this->sign($payload, 'wrong_secret');
        Webhook::parse($payload, $signature, $this->secret);
    }

    public function test_it_throws_for_invalid_json(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/not valid JSON/');

        $payload = 'not-json';
        $signature = $this->sign($payload);
        Webhook::parse($payload, $signature, $this->secret);
    }

    /**
     * @dataProvider requiredFieldProvider
     */
    public function test_it_throws_when_required_field_is_missing(string $field): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/missing required field: {$field}/");

        $data = [
            'id' => 'webhook_event_Qk8pRtSvWm2NjLhYcZaE',
            'resource' => 'webhook_event',
            'eventName' => 'order.paid',
            'entityType' => 'order',
            'entityId' => 'order_Hn5xWqVfKm8RjTgYbUcP',
        ];
        unset($data[$field]);

        $payload = json_encode($data);
        $signature = $this->sign($payload);
        Webhook::parse($payload, $signature, $this->secret);
    }

    public function requiredFieldProvider(): array
    {
        return [
            'id' => ['id'],
            'resource' => ['resource'],
            'eventName' => ['eventName'],
            'entityType' => ['entityType'],
            'entityId' => ['entityId'],
        ];
    }

    public function test_it_throws_when_object_is_not_an_object(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/"object" must be an object/');

        $payload = $this->makePayload(['object' => 'not-an-object']);
        $signature = $this->sign($payload);
        Webhook::parse($payload, $signature, $this->secret);
    }

    public function test_event_name_is_accessible_for_switch(): void
    {
        $payload = $this->makePayload(['eventName' => 'subscription.renewed']);
        $signature = $this->sign($payload);

        $event = Webhook::parse($payload, $signature, $this->secret);

        $this->assertSame('subscription.renewed', $event->eventName);
    }
}
