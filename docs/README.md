![Vatly API client for PHP](https://raw.githubusercontent.com/Vatly/vatly-api-php/main/art/banner.png)

# Getting Started

Official PHP SDK for the Vatly API. Handle subscriptions, one-off payments, tax compliance, and billing for your SaaS.

> **Looking for the exact field list?** These guides cover the common paths. The authoritative, machine-readable schema for every endpoint lives in the [OpenAPI spec](https://docs.vatly.com/openapi.yaml) — point your AI assistant or codegen tooling at it when an example leaves a field ambiguous.

## Installation



The SDK is in alpha, and every alpha release may include breaking changes. Allow alpha releases, then install the SDK via Composer. `prefer-stable` keeps your other dependencies on stable releases.




```bash
composer config minimum-stability alpha
composer config prefer-stable true
composer require vatly/vatly-api-php
```



---

## Quick start



Initialize the client with your API key and create a checkout.

### API Keys

Get your API keys from the [Vatly Dashboard](https://my.vatly.com) under **Settings > API**.

| Name | Type | Description |
| --- | --- | --- |
| `live_` | `prefix` | Production transactions, real charges. |
| `test_` | `prefix` | Sandbox testing, no real charges. |




```php
use Vatly\API\VatlyApiClient;

$vatly = new VatlyApiClient();
$vatly->setApiKey('live_your_api_key_here');

// Create a checkout
$checkout = $vatly->checkouts->create([
    'products' => [
        ['id' => 'subscription_plan_abc123', 'quantity' => 1]
    ],
    'redirectUrlSuccess' => 'https://yourapp.com/success',
    'redirectUrlCanceled' => 'https://yourapp.com/canceled',
]);

// Redirect customer to checkout
header('Location: ' . $checkout->links->checkoutUrl->href);
```



---

## Idempotency

The SDK automatically adds an `Idempotency-Key` header to every `POST` and `PATCH` request.
This is enabled by default, so checkout creation and subscription updates are already protected without extra configuration.

`GET` and `DELETE` requests do not include an idempotency header.

### Override the next mutating request

Use `setIdempotencyKey()` when you want to supply the key yourself.
The value is used for the next `POST` or `PATCH` request and then cleared automatically.

```php
$vatly->setIdempotencyKey('checkout-create-123');

$checkout = $vatly->checkouts->create([
    'products' => [
        ['id' => 'subscription_plan_abc123', 'quantity' => 1],
    ],
    'redirectUrlSuccess' => 'https://yourapp.com/success',
    'redirectUrlCanceled' => 'https://yourapp.com/canceled',
]);
```

This also works with resource methods that do not expose request options directly:

```php
$vatly->setIdempotencyKey('subscription-update-123');

$subscription->update([
    'quantity' => 2,
]);
```

### Per-request options on supported endpoint methods

Methods that accept a second or third options array can override the header with `idempotencyKey`.

```php
$checkout = $vatly->checkouts->create([...], [
    'idempotencyKey' => 'checkout-create-123',
]);

$subscription = $vatly->subscriptions->update('subscription_123', [
    'quantity' => 2,
], [
    'idempotencyKey' => 'subscription-update-123',
]);
```

### Custom generator or disable auto-generation

If you need a specific key format, provide your own generator implementation.

```php
use Vatly\API\HttpClient\Idempotency\IdempotencyKeyGeneratorContract;

final class MyIdempotencyKeyGenerator implements IdempotencyKeyGeneratorContract
{
    public function generate(): string
    {
        return bin2hex(random_bytes(16));
    }
}

$vatly->setIdempotencyKeyGenerator(new MyIdempotencyKeyGenerator());
```

To stop the SDK from generating keys automatically:

```php
$vatly->clearIdempotencyKeyGenerator();
```



---

## Resources



The SDK provides access to all Vatly API resources.




| Resource                                         | Description                   |
|--------------------------------------------------|-------------------------------|
| [Checkouts](/docs/Checkouts.md)                  | Create hosted payment pages   |
| [Customers](/docs/Customers.md)                  | Manage customer records       |
| [Subscriptions](/docs/Subscriptions.md)          | Recurring billing             |
| [Subscription Plans](/docs/SubscriptionPlans.md) | Define subscription products  |
| [One-Off Products](/docs/OneOffProducts.md)      | Single purchase products      |
| [Orders](/docs/Orders.md)                        | Transaction records           |
| [Refunds](/docs/Refunds.md)                      | Process refunds               |
| [Chargebacks](/docs/Chargebacks.md)              | Handle disputes               |
| [Webhooks](/docs/Webhooks.md)                    | Real-time event notifications |
| [Webhook Endpoints](/docs/WebhookEndpoints.md)   | Register delivery endpoints    |



---

## Error handling

Every exception the SDK throws implements `Vatly\API\Exceptions\VatlyException`, so one `catch` covers API errors and webhook signature failures.

When the API answers with an error, the SDK throws an `ApiException`, or the subclass for the HTTP status:

| Status | Exception | When |
| --- | --- | --- |
| `401` | `AuthenticationException` | The API key is missing or invalid. |
| `403` | `ForbiddenException` | The API key is invalid, revoked, or not allowed to perform this operation. |
| `404` | `NotFoundException` | The resource does not exist, or not in the API key's mode. |
| `409` | `ConflictException` | For example, an idempotency key reused with other parameters, or a request still in progress. |
| `422` | `ValidationException` | The request is invalid. `getErrors()` lists the messages per field. |
| `429` | `RateLimitException` | Too many requests. Wait `getRetryAfter()` seconds. |
| other | `ApiException` | Any other error status. |

An `ApiException` without a status code means no response arrived (for example, a connection error), or the SDK refused the call before sending it.

| Method | Returns |
| --- | --- |
| `getMessage()` | The API's error message. It never contains the request body or the API key. |
| `getStatusCode()` | The HTTP status, or `null` when there was no response. |
| `getErrors()` | Validation messages per field (dot notation), such as `['products.0.id' => ['The selected products.0.id is invalid.']]`. |
| `getDetails()` | The error's `details` object, such as `['idempotency_outcome' => 'in_progress']`. |
| `getRetryAfter()` | Seconds from the `Retry-After` header, sent on `429` and on a `409` while an idempotent request is in progress. |
| `getResponseBody()` | The raw response body. |

Branch on the exception class or status code, not on the message text.

```php
use Vatly\API\Exceptions\ApiException;
use Vatly\API\Exceptions\RateLimitException;
use Vatly\API\Exceptions\ValidationException;

try {
    $checkout = $vatly->checkouts->create([...]);
} catch (ValidationException $e) {
    foreach ($e->getErrors() as $field => $messages) {
        // e.g. 'products.0.id' => ['The selected products.0.id is invalid.']
    }
} catch (RateLimitException $e) {
    $waitSeconds = $e->getRetryAfter() ?? 60;
} catch (ApiException $e) {
    $status = $e->getStatusCode(); // null when no response arrived
}
```

---

## Requirements

- PHP 8.0+
- cURL extension
- JSON extension
