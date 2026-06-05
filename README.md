# Vatly API client for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vatly/vatly-api-php.svg?style=flat-square)](https://packagist.org/packages/vatly/vatly-api-php)
[![Tests](https://github.com/Vatly/vatly-api-php/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/Vatly/vatly-api-php/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/vatly/vatly-api-php.svg?style=flat-square)](https://packagist.org/packages/vatly/vatly-api-php)

Sell worldwide, today, with Vatly. Dedicated to EU based SAAS merchants and software companies, accept creditcard, PayPal, ApplePay, iDEAL and more.

## Using a framework?

This is the raw API client. If you're building a Laravel application, you almost certainly want [`vatly/vatly-laravel`](https://github.com/Vatly/vatly-laravel) instead — it provides a Cashier-style `Billable` trait, Eloquent models, a wired webhook endpoint, and event-bus integration on top of this SDK.

Other framework drivers (Symfony, WordPress) are on the roadmap. To build one, see the [Driver Author Guide](https://github.com/Vatly/vatly-fluent-php/blob/main/CONTRIBUTING.md) in [`vatly/vatly-fluent-php`](https://github.com/Vatly/vatly-fluent-php).

## Installation

You can install the package via composer:

```bash
composer require vatly/vatly-api-php
```

## Usage

```php
use Vatly\API\VatlyApiClient;

$vatly = new VatlyApiClient();
$vatly->setApiKey('test_your_api_key_here');

$vatly->checkouts->create([...]);
```

For detailed documentation, see [docs/README.md](docs/README.md).

> **Source of truth.** The complete, canonical schema for every request and response — including fields the guides below don't spell out — lives in the [OpenAPI spec](https://docs.vatly.com/openapi.yaml). If you (or an AI assistant) are unsure whether a field exists or what it's called, check the spec rather than guessing from an example.

## Idempotency

The SDK automatically sends an `Idempotency-Key` header on every `POST` and `PATCH` request.

```php
$checkout = $vatly->checkouts->create([
    'products' => [
        ['id' => 'subscription_plan_abc123', 'quantity' => 1],
    ],
    'redirectUrlSuccess' => 'https://yourapp.com/success',
    'redirectUrlCanceled' => 'https://yourapp.com/canceled',
]);
```

To set a custom key for the next mutating request, use `setIdempotencyKey()`. The manual key is cleared after the request is sent.

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

Some endpoint methods also accept a per-request `idempotencyKey` option:

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

You can replace or disable the automatic generator when needed:

```php
$vatly->setIdempotencyKeyGenerator(new MyIdempotencyKeyGenerator());
$vatly->clearIdempotencyKeyGenerator();
```

## Webhooks

Verify and parse incoming Vatly webhooks with `Webhook::parse()`, which returns a typed `WebhookPayload`:

```php
use Vatly\API\Webhooks\Webhook;

$event = Webhook::parse($rawBody, $signatureHeader, $webhookSecret);
```

For strongly-typed, per-event objects, this package also owns immutable event DTOs under `Vatly\API\Webhooks\Events\*` (e.g. `OrderPaid`, `RefundCompleted`, `SubscriptionStarted`). Money fields are `Vatly\API\Types\Money` (decimal-string + currency; call `$event->total->toCents()` / read `$event->total->currency` to flatten at your persistence edge) and the VAT breakdown is `Vatly\API\Types\TaxSummaryCollection`; order lines are `Vatly\API\Types\OrderLineData`. These are the canonical event shapes that higher-level integrations build on.

`Vatly\API\Webhooks\WebhookEventFactory` turns a verified payload into one of these typed events — verify, parse, and map all happen in api-php now. Pass it a `VatlyApiClient` and hand it the `WebhookReceived` from `fromPayload()`:

```php
use Vatly\API\Webhooks\Webhook;
use Vatly\API\Webhooks\WebhookEventFactory;

$payload = Webhook::parse($rawBody, $signatureHeader, $webhookSecret);

$factory = new WebhookEventFactory($client);
$event = $factory->createFromWebhook($factory->fromPayload($payload));
// e.g. $event instanceof Vatly\API\Webhooks\Events\OrderPaid
```

Because Vatly sends fat, HMAC-signed payloads — the delivery's `object` is byte-identical to the corresponding `GET /…/{id}` body — the factory builds every event straight from the signed payload (hydrating the matching api-php resource in memory for money/tax-bearing events). It makes **no follow-up API call**. Use `getSupportedEvents()` / `isSupported()` to inspect the event surface.

The incoming webhook payloads are described in the `webhooks:` section of the [OpenAPI spec](https://docs.vatly.com/openapi.yaml). See [docs/Webhooks.md](docs/Webhooks.md) for the full guide.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Send in a Pull Request if you'd like to contribute to this package.

## Security Vulnerabilities

In case of a security vulnerability, please shoot us an email at security@vatly.com.

## Credits

- [Vatly.com](https://www.vatly.com)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
