<?php

declare(strict_types=1);

namespace Vatly\Tests\API;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\HttpClient\CurlHttpClient;
use Vatly\API\VatlyApiClient;
use Vatly\Tests\BaseTestCase;

class VatlyApiClientTest extends BaseTestCase
{
    /** @test */
    public function itWiresUpACurlHttpClientWhenNoFactoryIsProvided(): void
    {
        $client = new VatlyApiClient;

        $this->assertInstanceOf(CurlHttpClient::class, $client->getHttpClient());
    }

    /** @test */
    public function setApiKeyAcceptsPipeCharacters(): void
    {
        $client = new VatlyApiClient;

        $client->setApiKey('test_1|dummy_key_with_pipe_character');

        $this->assertTrue(true); // Just making sure we don't get an exception in this test
    }

    /** @test */
    public function aRejectedApiKeyIsNotRepeatedInTheExceptionMessage(): void
    {
        try {
            (new VatlyApiClient)->setApiKey('live_sk_ab12cd34ef56gh78ij90!');
            $this->fail('Expected the API key to be refused.');
        } catch (ApiException $exception) {
            $this->assertStringStartsWith('Invalid API key.', $exception->getMessage());
            $this->assertStringNotContainsString('ab12cd34ef56gh78ij90', $exception->getMessage());
        }
    }

    /** @test */
    public function paginationLinksAreSentToTheConfiguredEndpointWhateverHostTheyName(): void
    {
        $this->client->setApiEndpoint('https://api.vatly.test');
        $this->httpClient->setSendReturnObjectFromArray([
            'count' => 0,
            'data' => [],
            'links' => [
                'self' => ['href' => 'https://api.vatly.test/v1/orders', 'type' => 'application/hal+json'],
                'next' => ['href' => 'https://attacker.example/v1/orders?startingAfter=order_abc&limit=2', 'type' => 'application/hal+json'],
                'prev' => ['href' => 'http://attacker.example/v1/orders?endingBefore=order_xyz', 'type' => 'application/hal+json'],
            ],
        ]);

        $page = $this->client->orders->page();
        $page->next();
        $page->previous();

        $this->assertWasSent('GET', 'https://api.vatly.test/v1/orders?startingAfter=order_abc&limit=2', [], null);
        $this->assertWasSent('GET', 'https://api.vatly.test/v1/orders?endingBefore=order_xyz', [], null);
        $this->assertSame(3, $this->httpClient->countRecordedSends());
    }

    /** @test */
    public function paginationLinksThatLeaveTheApiVersionAreRefused(): void
    {
        $this->httpClient->setSendReturnObjectFromArray([
            'count' => 0,
            'data' => [],
            'links' => [
                'self' => ['href' => 'https://api.vatly.com/v1/orders', 'type' => 'application/hal+json'],
                'next' => ['href' => 'https://api.vatly.com/v1/%2e%2e/admin?x=1', 'type' => 'application/hal+json'],
                'prev' => null,
            ],
        ]);

        $page = $this->client->orders->page();

        $this->expectException(ApiException::class);
        $page->next();
    }

    /** @test */
    public function fullUrlCallsOnlyReachTheConfiguredEndpoint(): void
    {
        $this->httpClient->setSendReturnObjectFromArray(['ok' => true]);

        $this->client->performHttpCallToFullUrl('GET', 'https://api.vatly.com/v1/orders');
        $this->assertWasSentOnly('GET', 'https://api.vatly.com/v1/orders', [], null);

        foreach (['https://api.vatly.com.attacker.example/v1/orders', 'https://api.vatly.com@attacker.example/v1/orders', 'http://api.vatly.com/v1/orders'] as $url) {
            try {
                $this->client->performHttpCallToFullUrl('GET', $url);
                $this->fail("Expected '{$url}' to be refused.");
            } catch (ApiException $exception) {
                $this->assertStringContainsString('not on the configured API endpoint', $exception->getMessage());
            }
        }

        $this->assertSame(1, $this->httpClient->countRecordedSends());
    }

    /** @test */
    public function theApiEndpointMustUseHttpsExceptForLocalhost(): void
    {
        foreach (['https://api.vatly.com/', 'http://localhost:8000', 'http://127.0.0.1:8000', 'http://[::1]:8000'] as $url) {
            $this->assertSame(rtrim($url, '/'), $this->client->setApiEndpoint($url)->getApiEndpoint());
        }

        foreach (['http://api.vatly.com', 'http://localhost.attacker.example', 'ftp://api.vatly.com', 'api.vatly.com'] as $url) {
            try {
                $this->client->setApiEndpoint($url);
                $this->fail("Expected '{$url}' to be refused.");
            } catch (ApiException $exception) {
                $this->assertStringContainsString('must use https', $exception->getMessage());
            }
        }

        $this->assertSame('http://[::1]:8000', $this->client->getApiEndpoint());
    }

    /** @test */
    public function curlRefusesPlainHttpToAnythingButLocalhost(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessageMatches('/Protocol .*http.* not supported|disabled/i');

        (new CurlHttpClient)->send('GET', 'http://api.vatly.com/v1/orders', [], null);
    }
}
