# Getting Started

Official PHP SDK for the Vatly API. Handle subscriptions, one-off payments, tax compliance, and billing for your SaaS.

## Installation



Install the SDK via Composer.




```bash
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
        ['id' => 'plan_abc123', 'quantity' => 1]
    ],
    'redirectUrlSuccess' => 'https://yourapp.com/success',
    'redirectUrlCanceled' => 'https://yourapp.com/canceled',
]);

// Redirect customer to checkout
header('Location: ' . $checkout->getCheckoutUrl());
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
        ['id' => 'plan_abc123', 'quantity' => 1],
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




| Resource | Description |
|----------|-------------|
| [Checkouts](/checkouts) | Create hosted payment pages |
| [Customers](/customers) | Manage customer records |
| [Subscriptions](/subscriptions) | Recurring billing |
| [Subscription Plans](/subscription-plans) | Define subscription products |
| [One-Off Products](/one-off-products) | Single purchase products |
| [Orders](/orders) | Transaction records |
| [Refunds](/refunds) | Process refunds |
| [Chargebacks](/chargebacks) | Handle disputes |
| [Webhooks](/webhooks) | Real-time event notifications |



---

## Error handling



The SDK throws specific exceptions for different error types.

### Exception types

| Name | Type | Description |
| --- | --- | --- |
| `ApiException` | `exception` | API errors, including authentication, validation, and transport failures. |




```php
use Vatly\API\Exceptions\ApiException;

try {
    $checkout = $vatly->checkouts->create([...]);
} catch (ApiException $e) {
    // API error (network, auth, validation, etc.)
    echo $e->getMessage();
}
```



---

## Requirements

- PHP 8.1+
- cURL extension
- JSON extension
