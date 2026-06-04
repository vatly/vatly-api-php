# Changelog

All notable changes to `vatly-api-php` will be documented in this file.

## Unreleased

### Added

- `Vatly\API\Webhooks\WebhookEventFactory` — turns a verified webhook into a typed `Vatly\API\Webhooks\Events\*` DTO entirely within api-php (verify → parse → map). Constructed with a single `VatlyApiClient` (`__construct(VatlyApiClient $apiClient)`); `createFromWebhook(WebhookReceived $webhook)` maps to the matching event, `fromPayload(WebhookPayload $payload)` adapts a parsed payload into a `WebhookReceived`, and `getSupportedEvents()` / `isSupported()` describe the surface. Because Vatly sends fat, HMAC-signed payloads (the `object` is byte-identical to the `GET /…/{id}` body), the factory builds every event from the signed payload — hydrating the matching api-php resource in memory for money/tax-bearing events via `ResourceFactory` — and makes **no follow-up API call**. This was previously owned by `vatly-fluent-php`; api-php is now the source of truth.
- `Vatly\API\Webhooks\Events\WebhookSetupReceived` — typed event DTO for the `webhook.setup` verification call. Carries the webhook envelope (`id`, `resource`, `eventName`, `entityType`, `entityId`, `testmode`, `createdAt`, `object`); build it from a `WebhookReceived` via `WebhookSetupReceived::fromWebhook()`. Previously this event had no DTO and fell through to `UnsupportedWebhookReceived`.

### Fixed

- Corrected stale docblocks on `Vatly\API\Webhooks\Events\OrderPaid` and `OrderChargebackReceived` that claimed the webhook event factory enriches the event via a follow-up `GetOrder` / `GetChargeback` API call. Events are now built from the signed webhook payload with no follow-up GET; the docblocks describe that flow.

### Changed

- **Renamed the `payment.failed` webhook event to `order.payment_failed`.** The `WebhookEventName::PAYMENT_FAILED` constant is now `WebhookEventName::ORDER_PAYMENT_FAILED` (value `order.payment_failed`), and the `PaymentFailed` event DTO is now `Vatly\API\Webhooks\Events\OrderPaymentFailed`. The OpenAPI `webhooks:` block and the `eventName` enums were updated to match. **Breaking** — there is no backwards-compatible alias; update references to the new name.
- **Webhook event DTO money fields are now `Vatly\API\Types\Money` instead of integer cents**, matching the rest of api-php (where API resources already expose money as `Money`). Affected fields: `OrderPaid`/`OrderPaymentFailed` `total` + `subtotal`; `RefundCompleted`/`RefundFailed`/`RefundCanceled` `total` + `subtotal`; `OrderChargebackReceived`/`OrderChargebackReversed` `total` + `subtotal` (now nullable `?Money`, defaulting to `null` on the sparse-webhook path); and `OrderLineData` `basePrice`/`total`/`subtotal`. Consumers read `$event->total->toCents()` for integer cents and `$event->total->currency` for the currency. `Money::toCents()` is unchanged. **Breaking.**
- **Dropped the now-redundant standalone `currency` field** from `OrderPaid`, `OrderPaymentFailed`, `RefundCompleted`, `RefundFailed`, and `RefundCanceled` — read `$event->total->currency` instead. The chargeback events (`OrderChargebackReceived`, `OrderChargebackReversed`) **keep** `currency`, since their `total` is nullable on the sparse-webhook path and can't reliably source it. **Breaking.**
- **Moved `OrderLineData` from `Vatly\API\Data\OrderLineData` to `Vatly\API\Types\OrderLineData`** (and removed the now-empty `Data\` namespace), so it sits alongside the other shared types. Update `use` statements accordingly. **Breaking.**
- **`CompatibilityChecker` hardened.** `MIN_PHP_VERSION` bumped from `7.4` to `8.0` to match composer's `php: ^8.0` constraint, and `checkCompatibility()` now verifies every required PHP extension (`bcmath`, `curl`, `intl`, `openssl`, `json`) instead of only `json` — notably `bcmath`, which `Money::toCents()` relies on. New `IncompatiblePlatformException` codes `INCOMPATIBLE_BCMATH_EXTENSION`, `INCOMPATIBLE_INTL_EXTENSION`, and `INCOMPATIBLE_OPENSSL_EXTENSION` cover the added checks.

## v0.1.0-alpha.16

### Added

- **Webhook event DTOs** now live in this package under `Vatly\API\Webhooks\Events\*` (immutable DTOs: `OrderPaid`, `OrderCanceled`, `OrderChargebackReceived`, `OrderChargebackReversed`, `OrderPaymentFailed`, `RefundCompleted`, `RefundFailed`, `RefundCanceled`, `SubscriptionStarted`, `SubscriptionBillingUpdated`, `SubscriptionResumed`, `SubscriptionCanceledImmediately`, `SubscriptionCanceledWithGracePeriod`, `SubscriptionCancellationGracePeriodCompleted`, `CheckoutPaid`, `CheckoutFailed`, `CheckoutCanceled`, `CheckoutExpired`, `WebhookReceived`, `UnsupportedWebhookReceived`). These were previously owned by `vatly-fluent-php`; api-php is now the source of truth. Part of the [webhook-DTO consolidation](https://github.com/) harmonization effort.
- `Vatly\API\Webhooks\Concerns\ParsesWebhookMandate` and `Vatly\API\Webhooks\Concerns\NormalizesWebhookMetadata` traits used by the event DTOs.
- `Vatly\API\Data\OrderLineData` — immutable per-line DTO (`vatlyId`, `description`, `quantity`, `basePrice`/`total`/`subtotal` in cents, `taxSummary` as `TaxSummaryCollection`, `productType`, `productId`) with `fromApiOrderLine()`.
- `Vatly\API\Types\Money::toCents(): int` — converts a decimal-string money value to integer cents using exact integer math (no float rounding).

### Notes

- Event money fields are integer cents and the tax breakdown is `Vatly\API\Types\TaxSummaryCollection` (replacing fluent's `Types\TaxSummary`). The incoming-webhook payload shapes are described in the `webhooks:` section of `openapi.yaml`.

