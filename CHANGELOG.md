# Changelog

All notable changes to `vatly-api-php` will be documented in this file.

## Unreleased

### Added

- **`customers->createPortalSession($id, [...])`** (`POST /v1/customers/{customerId}/portal-sessions`) — creates a short-lived, single-use hosted customer portal link. Returns a `Vatly\API\Types\PortalSession` (`url`, `expiresAt`, `returnUrl`). Optional body: `returnUrl` (absolute HTTPS URL, max 2048 bytes). The link is credential-bearing — redirect the customer to `url`; do not cache or log it.
- **`Subscription->cancellationReason`** — nullable string (`payment_failure` / `merchant_request` / `customer_request`, or `null`) explaining why a subscription was canceled. New `Vatly\API\Types\CancellationReason` constant class.
- **`subscription.canceled_for_nonpayment` webhook event** — payment recovery was exhausted, so the subscription was canceled. New `WebhookEventName::SUBSCRIPTION_CANCELED_FOR_NONPAYMENT` constant and typed DTO `Vatly\API\Webhooks\Events\SubscriptionCanceledForNonpayment` (exposes `customerId`, `subscriptionId`, `endsAt`, `testmode`, and `cancellationReason`), wired into `WebhookEventFactory` and reported by `getSupportedEvents()` / `isSupported()`.

### Fixed

- **`ScheduledSubscriptionUpdate` now carries `effectiveAt`** — the next-renewal date a scheduled change applies (nullable ISO 8601 date-time), matching the spec where it is a required field of `scheduledUpdate`. It is hydrated on both the `subscription.update_scheduled` webhook and the REST `Subscription` resource.
- **`Subscription->scheduledUpdate` is now the typed `ScheduledSubscriptionUpdate`** (previously exposed as raw `\stdClass|null` so `effectiveAt` wasn't dropped). Both the REST resource and the webhook path (`SubscriptionUpdateScheduled`) hydrate the same typed class. Read `$subscription->scheduledUpdate->effectiveAt`, `->basePrice->value`, etc.

### Added

- **`oneOffProducts->create([...])`** (`POST /v1/one-off-products`). Body: `name` (3–255), `description`, `basePrice` (`['value' => '299.00', 'currency' => 'EUR']`), `productType` (`saas` or `ebook`). A product created with a `live_` token starts in `pending` status and must be approved by Vatly before use in checkouts; a `test_` token auto-approves it (`active`).
- **`subscriptionPlans->create([...])`** (`POST /v1/subscription-plans`). Body: `name` (3–255), `description`, `basePrice`, `productType` (`saas` only), `interval` (`day` is sandbox-only; live plans support `week`/`month`/`year`), `intervalCount` (≤ 365 days / 52 weeks / 12 months; ignored for `year`). Same `pending`/`active` approval behaviour as one-off products.
- **One-off product update/archive lifecycle**: `oneOffProducts->update($id, [...])` (`PATCH`), `oneOffProducts->archive($id)` (`POST .../archive`, `204`), and `oneOffProducts->unarchive($id)` (`DELETE .../archive`). Convenience methods `$product->update()`, `$product->archive()`, `$product->unarchive()`, and `$product->isArchived()` on the resource.
- **Subscription plan update/archive lifecycle**: `subscriptionPlans->update($id, [...])`, `subscriptionPlans->archive($id)`, and `subscriptionPlans->unarchive($id)`, plus the same convenience methods on the `SubscriptionPlan` resource.
- **`customers->listByEmail($email)`** — recover a customer id by email (`GET /v1/customers?email=...`); returns a (possibly empty) `CustomerCollection`.
- New fields on the `OneOffProduct` and `SubscriptionPlan` resources: `taxBehavior` (`exclusive`/`inclusive`), `productType`, `archivedAt`, `pendingUpdates`, `updateStatus`. `taxBehavior` is also accepted (optional, defaults to `exclusive`) on `create`.
- `Checkout` resource now exposes `locale`, and `checkouts->create([...])` accepts a `locale` (folded language: `en`, `de`, `fr`, `nl`, `es`, `it`, `pt`, `pl`).
- `Subscription` resource now exposes `scheduledUpdate` (the target values of a change scheduled for the next billing cycle; always present, `null` when none).
- `testHelpers->fastForwardSubscriptionRenewal($id, [...])` now accepts an optional body to force the renewal payment outcome (`paymentStatus`, `failureReason`).
- Ten new `WebhookEventName` constants for the product events: `one_off_product.update_submitted`/`update_approved`/`update_rejected`/`archived`/`unarchived` and the matching `subscription_plan.*`.
- **Typed webhook event DTOs for all ten product events** (`Vatly\API\Webhooks\Events\OneOffProduct*` and `SubscriptionPlan*`). `WebhookEventFactory` hydrates each event's `object` straight into the `OneOffProduct` / `SubscriptionPlan` resource (`$event->oneOffProduct` / `$event->subscriptionPlan`) with no follow-up API call, and each DTO exposes the resource id + `testmode` and a `VATLY_EVENT_NAME` constant. They are now reported by `getSupportedEvents()` / `isSupported()`.
- New type constant classes `Vatly\API\Types\TaxBehavior` and `Vatly\API\Types\UpdateStatus`.

### Changed

- Synced vendored `openapi.yaml` to the latest upstream spec (adds the one-off-product and subscription-plan create operations and their request schemas).
- Corrected `docs/SubscriptionPlans.md` property table to match the resource (`basePrice` as `Money`; statuses `active`/`pending`/`rejected`) — it previously documented non-existent `amount`/`currency`/`trialDays` fields.
- Product listings (`oneOffProducts->page()`, `subscriptionPlans->page()`) document the `includeArchived` filter.

## v0.1.0-alpha.23

### Added

- **Customer `name`.** `Vatly\API\Resources\Customer` now exposes a nullable `public ?string $name` (identity field for communication — distinct from the billing name on invoices), and `customers->create([...])` accepts it.
- **`customers->update($id, [...])`** (`PATCH /v1/customers/{id}`). Updates a customer's identity fields — only `name` and `email`, both optional. Billing-address fields are not editable here. Also available on the resource as `$customer->update([...])`.
- **Webhook Endpoints resource (CRUD).** New `webhookEndpoints` accessor on `VatlyApiClient` with `page()`, `create()`, `get()`, `update()`, and `delete()`, backed by `Vatly\API\Resources\WebhookEndpoint` (`id` (`webhook_` prefix), `resource: "webhook_endpoint"`, `testmode`, `url`, `createdAt`, `links.self`) and `WebhookEndpointCollection`. There is at most one endpoint per mode. The signing `secret` is **write-only** (min 10 chars): sent on create/update, never returned. `delete()` returns no content (`204`).
- **Two new subscription webhook events.** `Vatly\API\Webhooks\Events\SubscriptionUpdated` (`subscription.updated` — an immediate plan/price/interval/quantity change; `object` is the subscription with its new values) and `Vatly\API\Webhooks\Events\SubscriptionUpdateScheduled` (`subscription.update_scheduled` — a change scheduled for the next cycle; the target values are exposed as a typed `Vatly\API\Types\ScheduledSubscriptionUpdate` from `object.scheduledUpdate`). New `WebhookEventName::SUBSCRIPTION_UPDATED` / `SUBSCRIPTION_UPDATE_SCHEDULED` constants; both are mapped by `WebhookEventFactory` and listed in `getSupportedEvents()`.

### Changed

- **Subscription update accepts `price`.** `subscriptions->update($id, [...])` now supports a `price` object (`['value' => '99.99', 'currency' => 'EUR']`) that must match the subscription's currency; combined with `subscriptionPlanId` it overrides the new plan's default price. `quantity` is the **new total** (not additive). At least one of `subscriptionPlanId`, `quantity`, or `price` is required. (The SDK passes the body through, so no signature change — see the updated `docs/Subscriptions.md`.)
- **Synced `openapi.yaml`** to the latest published vatlify spec (version 1.0): adds the `updateCustomer` (`PATCH /v1/customers/{id}`) and webhook-endpoints operations, the customer `name` field, the subscription-update `price` object, the `subscription.updated` / `subscription.update_scheduled` webhook events, and removes the deleted `simulate-failure` test-helper route. PSP-neutral wording throughout.

### Removed

- **The `simulate-failure` test helper.** `TestHelpersEndpoint::simulatePaymentFailure()` (and its tests) is removed — the upstream `POST /v1/test-helpers/mandated-payments/{transactionId}/simulate-failure` route was deleted upstream. The fast-forward-renewal helper is unchanged.

- `Vatly\API\Webhooks\WebhookEventFactory` — turns a verified webhook into a typed `Vatly\API\Webhooks\Events\*` DTO entirely within api-php (verify → parse → map). Constructed with a single `VatlyApiClient` (`__construct(VatlyApiClient $apiClient)`); `createFromWebhook(WebhookReceived $webhook)` maps to the matching event, `fromPayload(WebhookPayload $payload)` adapts a parsed payload into a `WebhookReceived`, and `getSupportedEvents()` / `isSupported()` describe the surface. Because Vatly sends fat, HMAC-signed payloads (the `object` is byte-identical to the `GET /…/{id}` body), the factory builds every event from the signed payload — hydrating the matching api-php resource in memory for money/tax-bearing events via `ResourceFactory` — and makes **no follow-up API call**. This was previously owned by `vatly-fluent-php`; api-php is now the source of truth.
- `Vatly\API\Webhooks\Events\WebhookSetupReceived` — typed event DTO for the `webhook.setup` verification call. Carries the webhook envelope (`id`, `resource`, `eventName`, `entityType`, `entityId`, `testmode`, `createdAt`, `object`); build it from a `WebhookReceived` via `WebhookSetupReceived::fromWebhook()`. Previously this event had no DTO and fell through to `UnsupportedWebhookReceived`.
- **`testmode` on every business event DTO.** All `Vatly\API\Webhooks\Events\*` order/subscription/refund/chargeback/checkout events now carry a `public bool $testmode`, populated from the source resource (`$resource->testmode` on the enriched path) or the webhook envelope (`$webhook->testmode` on the payload path). This mirrors the envelope events (`WebhookReceived`/`WebhookSetupReceived`/`UnsupportedWebhookReceived`), which already exposed it, and lets downstream consumers segregate test/live records and select the matching API key per record. **Breaking** — `testmode` is a required constructor parameter; all events are built via their `from*()` mappers, so no call-site change is needed unless you construct an event directly.

### Fixed

- Corrected renamed endpoint paths (`invoice-update-link`, `billing-update-link`) per vatlify [#1495](https://github.com/). `OrderEndpoint::requestAddressUpdateLink()` now `POST`s to `/orders/{id}/invoice-update-link` (was `/request-address-update-link`), and `SubscriptionEndpoint::updateBilling()` now `POST`s to `/subscriptions/{id}/billing-update-link` (was a `PATCH` to `/update-billing`). The PHP method names are unchanged. Both previously hit dead paths.
- `Vatly\API\Resources\Links\OrderLinks::$customer` is now nullable (`?Link`). The stabilized v1 response contract now always includes the `customer` link on an order and sets it to `null` for orders with no associated customer (previously the field was omitted). Hydrating such an order into the non-nullable property threw a `TypeError`.
- Corrected stale docblocks on `Vatly\API\Webhooks\Events\OrderPaid` and `OrderChargebackReceived` that claimed the webhook event factory enriches the event via a follow-up `GetOrder` / `GetChargeback` API call. Events are now built from the signed webhook payload with no follow-up GET; the docblocks describe that flow.

### Changed

- **Synced `openapi.yaml` to the latest published vatlify spec ([#1495](https://github.com/), "stabilize v1 response contract + fix OpenAPI inconsistencies").** Substantive changes: every operation gained a `default` → `UnexpectedError` response (a catch-all consolidating the previous `BadRequest` / `TooManyRequests` / `InternalServerError` responses, branching on status code with the `Retry-After` header on `429`); the `AddressUpdateLink` schema was renamed to `InvoiceUpdateLink`; and the `Checkout`/`Order`/`Refund`/`Chargeback` resources now always include their `customerId` / `orderId` / `order` / `customer` fields-and-links (set to `null` rather than omitted) and mark them `required`, with the `Order` `customer` link now `oneOf [Link, null]`. The rest is descriptive (the `order.customerInvoice` link is now `text/html`, `prod_`/`plan_` checkout-id examples became `one_off_product_`/`subscription_plan_`, and various example/description tidy-ups). See the `### Fixed` `OrderLinks::$customer` entry for the one matching SDK change.
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

