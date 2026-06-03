# Changelog

All notable changes to `vatly-api-php` will be documented in this file.

## Unreleased

### Added

- `Vatly\API\Webhooks\Events\WebhookSetupReceived` — typed event DTO for the `webhook.setup` verification call. Carries the webhook envelope (`id`, `resource`, `eventName`, `entityType`, `entityId`, `testmode`, `createdAt`, `object`); build it from a `WebhookReceived` via `WebhookSetupReceived::fromWebhook()`. Previously this event had no DTO and fell through to `UnsupportedWebhookReceived`.

### Changed

- **Renamed the `payment.failed` webhook event to `order.payment_failed`.** The `WebhookEventName::PAYMENT_FAILED` constant is now `WebhookEventName::ORDER_PAYMENT_FAILED` (value `order.payment_failed`), and the `PaymentFailed` event DTO is now `Vatly\API\Webhooks\Events\OrderPaymentFailed`. The OpenAPI `webhooks:` block and the `eventName` enums were updated to match. **Breaking** — there is no backwards-compatible alias; update references to the new name.

## v0.1.0-alpha.16

### Added

- **Webhook event DTOs** now live in this package under `Vatly\API\Webhooks\Events\*` (immutable DTOs: `OrderPaid`, `OrderCanceled`, `OrderChargebackReceived`, `OrderChargebackReversed`, `OrderPaymentFailed`, `RefundCompleted`, `RefundFailed`, `RefundCanceled`, `SubscriptionStarted`, `SubscriptionBillingUpdated`, `SubscriptionResumed`, `SubscriptionCanceledImmediately`, `SubscriptionCanceledWithGracePeriod`, `SubscriptionCancellationGracePeriodCompleted`, `CheckoutPaid`, `CheckoutFailed`, `CheckoutCanceled`, `CheckoutExpired`, `WebhookReceived`, `UnsupportedWebhookReceived`). These were previously owned by `vatly-fluent-php`; api-php is now the source of truth. Part of the [webhook-DTO consolidation](https://github.com/) harmonization effort.
- `Vatly\API\Webhooks\Concerns\ParsesWebhookMandate` and `Vatly\API\Webhooks\Concerns\NormalizesWebhookMetadata` traits used by the event DTOs.
- `Vatly\API\Data\OrderLineData` — immutable per-line DTO (`vatlyId`, `description`, `quantity`, `basePrice`/`total`/`subtotal` in cents, `taxSummary` as `TaxSummaryCollection`, `productType`, `productId`) with `fromApiOrderLine()`.
- `Vatly\API\Types\Money::toCents(): int` — converts a decimal-string money value to integer cents using exact integer math (no float rounding).

### Notes

- Event money fields are integer cents and the tax breakdown is `Vatly\API\Types\TaxSummaryCollection` (replacing fluent's `Types\TaxSummary`). The incoming-webhook payload shapes are described in the `webhooks:` section of `openapi.yaml`.

