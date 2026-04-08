<?php

declare(strict_types=1);

namespace Vatly\Tests;

use PHPUnit\Framework\TestCase;
use Vatly\API\HttpClient\Idempotency\FakeIdempotencyKeyGenerator;
use Vatly\API\VatlyApiClient;
use Vatly\Tests\API\HttpClient\SpyHttpClient;
use Vatly\Tests\API\HttpClient\SpyHttpClientFactory;

abstract class BaseTestCase extends TestCase
{
    /**
     * @var \Vatly\Tests\API\HttpClient\SpyHttpClient
     */
    protected SpyHttpClient $httpClient;

    /**
     * @var \Vatly\API\VatlyApiClient
     */
    protected VatlyApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = new SpyHttpClient;
        $this->client = new VatlyApiClient(new SpyHttpClientFactory($this->httpClient));
        $this->client->setApiKey('test_spy_dummy_dummy_dummy_dummy');
        $this->client->setIdempotencyKeyGenerator(new FakeIdempotencyKeyGenerator());
    }

    public function setClientSendReturnObject(object $returnObject): self
    {
        $this->httpClient->setSendReturnObject($returnObject);

        return $this;
    }

    public function assertWasSent(
        string $httpMethod,
        string $url,
        array $headers,
        ?string $httpBody
    ): void {
        $message = 'Expected message was not sent.';

        $this->assertTrue(
            $this->httpClient->wasSent(
                $httpMethod,
                $url,
                $headers,
                $httpBody,
            ),
            $message,
        );
    }

    public function assertWasSentOnly(
        string $httpMethod,
        string $url,
        array $headers,
        ?string $httpBody
    ): void {
        $message = 'Expected message was not sent.';

        $this->assertTrue(
            $this->httpClient->wasSentOnly(
                $httpMethod,
                $url,
                $headers,
                $httpBody,
            ),
            $message,
        );
    }
}
