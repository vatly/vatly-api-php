<?php

declare(strict_types=1);

namespace Vatly\API;

use Vatly\API\Endpoints\ChargebackEndpoint;
use Vatly\API\Endpoints\CheckoutEndpoint;
use Vatly\API\Endpoints\CustomerEndpoint;
use Vatly\API\Endpoints\CustomerSubscriptionEndpoint;
use Vatly\API\Endpoints\OneOffProductEndpoint;
use Vatly\API\Endpoints\OrderChargebackEndpoint;
use Vatly\API\Endpoints\OrderEndpoint;
use Vatly\API\Endpoints\OrderRefundEndpoint;
use Vatly\API\Endpoints\RefundEndpoint;
use Vatly\API\Endpoints\SubscriptionEndpoint;
use Vatly\API\Endpoints\SubscriptionPlanEndpoint;
use Vatly\API\Endpoints\TestHelpersEndpoint;
use Vatly\API\Endpoints\WebhookEndpointEndpoint;
use Vatly\API\Endpoints\WebhookEventEndpoint;
use Vatly\API\Exceptions\ApiException;
use Vatly\API\Exceptions\HttpAdapterDoesNotSupportDebuggingException;
use Vatly\API\HttpClient\DefaultHttpClientFactory;
use Vatly\API\HttpClient\HttpClientFactoryInterface;
use Vatly\API\HttpClient\HttpClientInterface;
use Vatly\API\HttpClient\Idempotency\DefaultIdempotencyKeyGenerator;
use Vatly\API\Support\LoopbackHost;
use Vatly\API\Traits\HandlesIdempotency;

class VatlyApiClient
{
    use HandlesIdempotency;
    /**
     * The version of this client.
     */
    public const CLIENT_VERSION = '0.1.0-alpha.40';

    /**
     * Endpoint of the remote API.
     */
    public const API_ENDPOINT = 'https://api.vatly.com';

    /**
     * Version of the remote API.
     */
    public const API_VERSION = "v1";

    /**
     * HTTP Methods
     */
    public const HTTP_GET = "GET";
    public const HTTP_POST = "POST";
    public const HTTP_DELETE = "DELETE";
    public const HTTP_PATCH = "PATCH";

    /**
     * HTTP status code for an empty ok response.
     */
    public const HTTP_NO_CONTENT = 204;

    /**
     * @var \Vatly\API\HttpClient\HttpClientInterface
     */
    protected HttpClientInterface $httpClient;

    /**
     * @var string[]
     */
    protected array $versionStrings;

    protected string $apiEndpoint = self::API_ENDPOINT;

    protected ?string $apiKey;

    public CheckoutEndpoint $checkouts;

    public OrderEndpoint $orders;

    public OneOffProductEndpoint $oneOffProducts;
    public SubscriptionPlanEndpoint $subscriptionPlans;

    public CustomerEndpoint $customers;
    public CustomerSubscriptionEndpoint $customerSubscriptions;
    public ChargebackEndpoint $chargebacks;
    public OrderChargebackEndpoint $orderChargebacks;
    public RefundEndpoint $refunds;
    public OrderRefundEndpoint $orderRefunds;
    public SubscriptionEndpoint $subscriptions;
    public TestHelpersEndpoint $testHelpers;
    public WebhookEventEndpoint $webhookEvents;
    public WebhookEndpointEndpoint $webhookEndpoints;

    /**
     * @throws \Vatly\API\Exceptions\IncompatiblePlatformException
     */
    public function __construct(
        ?HttpClientFactoryInterface $httpClientFactory = null,
        ?CompatibilityChecker $compatibilityChecker = null
    ) {
        if (! $compatibilityChecker) {
            $compatibilityChecker = new CompatibilityChecker;
        }
        $compatibilityChecker->checkCompatibility();


        if ($httpClientFactory) {
            $this->httpClient = $httpClientFactory->make();
        } else {
            $this->httpClient = (new DefaultHttpClientFactory)->make();
        }

        $this->idempotencyKeyGenerator = new DefaultIdempotencyKeyGenerator();

        $this->initializeVersionString();
        $this->initializeEndpoints();
    }

    protected function initializeEndpoints(): void
    {
        $this->checkouts = new CheckoutEndpoint($this);
        $this->orders = new OrderEndpoint($this);
        $this->oneOffProducts = new OneOffProductEndpoint($this);
        $this->subscriptionPlans = new SubscriptionPlanEndpoint($this);
        $this->customers = new CustomerEndpoint($this);
        $this->chargebacks = new ChargebackEndpoint($this);
        $this->orderChargebacks = new OrderChargebackEndpoint($this);
        $this->refunds = new RefundEndpoint($this);
        $this->orderRefunds = new OrderRefundEndpoint($this);
        $this->subscriptions = new SubscriptionEndpoint($this);
        $this->customerSubscriptions = new CustomerSubscriptionEndpoint($this);
        $this->testHelpers = new TestHelpersEndpoint($this);
        $this->webhookEvents = new WebhookEventEndpoint($this);
        $this->webhookEndpoints = new WebhookEndpointEndpoint($this);
    }

    protected function initializeVersionString(): void
    {
        $this->addVersionString('vatly/vatly-api-php/'.self::CLIENT_VERSION);
        $this->addVersionString('PHP/'.phpversion());
        $this->addVersionString($this->httpClient->versionString());
    }

    /**
     * @return string[]
     */
    public function getVersionStrings(): array
    {
        return $this->versionStrings;
    }

    /**
     * Add a token to the `User-Agent`, after the ones already there.
     *
     * Every package or integration between the merchant's code and this client
     * adds its own token, so Vatly sees the whole chain. Use the Composer package
     * name (or the plugin's slug) and its version: `vendor/package/1.2.3`.
     * Whitespace becomes `-`.
     *
     * @example $vatly->addVersionString('vatly/vatly-fluent-php/0.8.0-alpha.36');
     */
    public function addVersionString(string $versionString): void
    {
        $this->versionStrings[] = str_replace([' ', "\t", "\n", "\r"], '-', $versionString);
    }

    /**
     * @throws ApiException when the URL does not use https (plain http is only allowed for localhost)
     */
    public function setApiEndpoint(string $url): VatlyApiClient
    {
        $url = rtrim(trim($url), '/');
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if ($scheme !== 'https' && ! ($scheme === 'http' && LoopbackHost::isLoopbackUrl($url))) {
            throw new ApiException("Invalid API endpoint: '{$url}'. The API endpoint must use https, except for localhost.");
        }

        $this->apiEndpoint = $url;

        return $this;
    }

    public function getApiEndpoint(): string
    {
        return $this->apiEndpoint;
    }

    /**
     * The Vatly API version this client speaks. A new API version comes with a new major version of this package.
     */
    public function getApiVersion(): string
    {
        return self::API_VERSION;
    }

    public function setApiKey(string $apiKey): VatlyApiClient
    {
        $apiKey = trim($apiKey);

        if (! preg_match('/^(live|test)_[\w|]{18,}$/', $apiKey)) {
            throw new ApiException("Invalid API key. An API key must start with 'test_' or 'live_' and must be at least 18 characters long.");
        }

        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Perform a http call. This method is used by the resource specific classes.
     *
     * @param string $httpMethod
     * @param string $apiMethod
     * @param string|null $httpBody
     *
     * @return \stdClass
     * @throws ApiException
     */
    public function performHttpCall(string $httpMethod, string $apiMethod, ?string $httpBody = null, array $requestHeaders = []): ?object
    {
        $url = $this->apiEndpoint . "/" . self::API_VERSION . "/" . $apiMethod;

        return $this->sendRequest($httpMethod, $url, $httpBody, $requestHeaders);
    }

    /**
     * @throws \Vatly\API\Exceptions\ApiException
     */
    private function sendRequest(string $httpMethod, string $url, ?string $httpBody, array $requestHeaders): ?object
    {
        if (empty($this->apiKey)) {
            throw new ApiException("You have not set an API key. Please use setApiKey() to set the API key.");
        }

        $headers = [
            'Accept' => "application/json",
            'Authorization' => "Bearer {$this->apiKey}",
            'User-Agent' => implode(' ', $this->versionStrings),
        ];

        if ($httpBody !== null) {
            $headers['Content-Type'] = "application/json";
        }

        if (function_exists("php_uname")) {
            $headers['X-Vatly-Client-Info'] = php_uname();
        }

        // Auto-attach Idempotency-Key on every mutating verb the Vatly API
        // dedupes for. The API accepts the header on POST/PATCH/PUT/DELETE;
        // PUT isn't enumerated here because no spec
        // endpoint uses it today and CurlHttpClient doesn't wire it up.
        if (in_array($httpMethod, [self::HTTP_POST, self::HTTP_PATCH, self::HTTP_DELETE])) {
            $idempotencyKey = $this->idempotencyKey;

            if ($idempotencyKey === null && $this->idempotencyKeyGenerator !== null) {
                $idempotencyKey = $this->idempotencyKeyGenerator->generate();
            }

            if ($idempotencyKey !== null) {
                $headers['Idempotency-Key'] = $idempotencyKey;
            }
        }

        $headers = array_merge($headers, $requestHeaders);

        $response = $this->httpClient->send($httpMethod, $url, $headers, $httpBody);

        $this->resetIdempotencyKey();

        return $response;
    }

    /**
     * Enable debugging mode. If debugging mode is enabled, the attempted request will be included in the ApiException.
     * By default, debugging is disabled to prevent leaking sensitive request data into exception logs.
     *
     * @throws \Vatly\Api\Exceptions\HttpAdapterDoesNotSupportDebuggingException
     */
    public function enableDebugging(): void
    {
        if (
            ! method_exists($this->httpClient, 'supportsDebugging')
            || ! $this->httpClient->supportsDebugging()
        ) {
            throw new HttpAdapterDoesNotSupportDebuggingException(
                "Debugging is not supported by " . get_class($this->httpClient) . "."
            );
        }

        $this->httpClient->enableDebugging();
    }

    /**
     * Disable debugging mode. If debugging mode is enabled, the attempted request will be included in the ApiException.
     * By default, debugging is disabled to prevent leaking sensitive request data into exception logs.
     *
     * @throws \Vatly\Api\Exceptions\HttpAdapterDoesNotSupportDebuggingException
     */
    public function disableDebugging(): void
    {
        if (
            ! method_exists($this->httpClient, 'supportsDebugging')
            || ! $this->httpClient->supportsDebugging()
        ) {
            throw new HttpAdapterDoesNotSupportDebuggingException(
                "Debugging is not supported by " . get_class($this->httpClient) . "."
            );
        }

        $this->httpClient->disableDebugging();
    }

    public function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }
}
