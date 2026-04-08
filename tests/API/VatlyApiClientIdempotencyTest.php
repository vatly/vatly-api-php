<?php

declare(strict_types=1);

namespace Vatly\Tests\API;

use Vatly\API\HttpClient\Idempotency\DefaultIdempotencyKeyGenerator;
use Vatly\API\VatlyApiClient;
use Vatly\Tests\BaseTestCase;

class VatlyApiClientIdempotencyTest extends BaseTestCase
{
    /** @test */
    public function auto_generates_idempotency_key_on_post_by_default()
    {
        $this->client->setIdempotencyKeyGenerator(new DefaultIdempotencyKeyGenerator());

        $this->httpClient->setSendReturnObjectFromArray(['id' => 'checkout_123', 'resource' => 'checkout']);

        $this->client->performHttpCall(VatlyApiClient::HTTP_POST, 'checkouts', '{"test":true}');

        $headers = $this->httpClient->lastSentHeaders();
        $this->assertArrayHasKey('Idempotency-Key', $headers);
        $this->assertEquals(16, strlen($headers['Idempotency-Key']));
    }

    /** @test */
    public function manual_key_overrides_generator()
    {
        $this->client->setIdempotencyKey('my-custom-key');

        $this->httpClient->setSendReturnObjectFromArray(['id' => 'checkout_123', 'resource' => 'checkout']);

        $this->client->performHttpCall(VatlyApiClient::HTTP_POST, 'checkouts', '{"test":true}');

        $headers = $this->httpClient->lastSentHeaders();
        $this->assertEquals('my-custom-key', $headers['Idempotency-Key']);
    }

    /** @test */
    public function manual_key_auto_resets_after_request()
    {
        $this->client->setIdempotencyKey('my-custom-key');

        $this->httpClient->setSendReturnObjectFromArray(['id' => 'checkout_123', 'resource' => 'checkout']);

        $this->client->performHttpCall(VatlyApiClient::HTTP_POST, 'checkouts', '{"test":true}');

        $this->assertNull($this->client->getIdempotencyKey());

        // Second request should use the generator, not the manual key
        $this->httpClient->setSendReturnObjectFromArray(['id' => 'checkout_456', 'resource' => 'checkout']);
        $this->client->performHttpCall(VatlyApiClient::HTTP_POST, 'checkouts', '{"test":true}');

        $headers = $this->httpClient->lastSentHeaders();
        $this->assertEquals('fake-idempotency-key', $headers['Idempotency-Key']);
    }

    /** @test */
    public function no_idempotency_key_header_on_get()
    {
        $this->httpClient->setSendReturnObjectFromArray(['id' => 'checkout_123', 'resource' => 'checkout']);

        $this->client->performHttpCall(VatlyApiClient::HTTP_GET, 'checkouts/checkout_123');

        $headers = $this->httpClient->lastSentHeaders();
        $this->assertArrayNotHasKey('Idempotency-Key', $headers);
    }

    /** @test */
    public function no_idempotency_key_header_on_delete()
    {
        $this->httpClient->setSendReturnNull();

        $this->client->performHttpCall(VatlyApiClient::HTTP_DELETE, 'checkouts/checkout_123');

        $headers = $this->httpClient->lastSentHeaders();
        $this->assertArrayNotHasKey('Idempotency-Key', $headers);
    }

    /** @test */
    public function clear_generator_disables_auto_generation()
    {
        $this->client->clearIdempotencyKeyGenerator();

        $this->httpClient->setSendReturnObjectFromArray(['id' => 'checkout_123', 'resource' => 'checkout']);

        $this->client->performHttpCall(VatlyApiClient::HTTP_POST, 'checkouts', '{"test":true}');

        $headers = $this->httpClient->lastSentHeaders();
        $this->assertArrayNotHasKey('Idempotency-Key', $headers);
    }

    /** @test */
    public function per_request_idempotency_key_via_request_headers()
    {
        $this->httpClient->setSendReturnObjectFromArray(['id' => 'checkout_123', 'resource' => 'checkout']);

        $this->client->performHttpCall(
            VatlyApiClient::HTTP_POST,
            'checkouts',
            '{"test":true}',
            ['Idempotency-Key' => 'per-request-key']
        );

        $headers = $this->httpClient->lastSentHeaders();
        $this->assertEquals('per-request-key', $headers['Idempotency-Key']);
    }
}
