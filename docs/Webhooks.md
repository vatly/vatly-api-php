# Webhooks

Vatly sends webhooks to notify your application when events happen — for example, an order being paid, a refund completing, or a subscription being canceled.

## Webhook events

The `eventName` field on a delivery identifies what happened. See [`Vatly\API\Types\WebhookEventName`](../src/API/Types/WebhookEventName.php) for the constants.

| Event | Description |
|-------|-------------|
| `order.paid` | Order payment was successful. |
| `order.canceled` | Order was canceled. |
| `order.chargeback_received` | Chargeback was received for an order. |
| `order.chargeback_reversed` | Chargeback was reversed. |
| `order.payment_failed` | A payment failed and a dunning process was initiated for the order. |
| `refund.completed` | Refund was processed successfully. |
| `refund.failed` | Refund processing failed. |
| `refund.canceled` | Refund was canceled. |
| `subscription.started` | Subscription was started. |
| `subscription.canceled_immediately` | Subscription was canceled immediately. |
| `subscription.canceled_with_grace_period` | Subscription was canceled, customer keeps access until the period ends. |
| `subscription.cancellation_grace_period_completed` | Grace period after cancellation ended. |
| `subscription.resumed` | A canceled subscription was resumed during its grace period. |
| `subscription.updated` | A subscription changed immediately (plan, price, interval or quantity). `object` is the subscription with its new values. |
| `subscription.update_scheduled` | A change was scheduled for the next billing cycle. `object` is the subscription's current state; the target values are in `object.scheduledUpdate` (`subscriptionPlanId`, `name`, `description`, `basePrice`, `quantity`, `interval`, `intervalCount`). |
| `checkout.paid` | Checkout was paid successfully. |
| `checkout.failed` | Checkout payment failed. |
| `checkout.canceled` | Checkout was canceled. |
| `checkout.expired` | Checkout session expired. |
| `one_off_product.update_submitted` | An update to a one-off product was submitted for review. |
| `one_off_product.update_approved` | A submitted one-off product update was approved and applied. |
| `one_off_product.update_rejected` | A submitted one-off product update was rejected. |
| `one_off_product.archived` | A one-off product was archived (taken out of the sellable catalogue). |
| `one_off_product.unarchived` | An archived one-off product was put back on sale. |
| `subscription_plan.update_submitted` | An update to a subscription plan was submitted for review. |
| `subscription_plan.update_approved` | A submitted subscription plan update was approved and applied. |
| `subscription_plan.update_rejected` | A submitted subscription plan update was rejected. |
| `subscription_plan.archived` | A subscription plan was archived (closed to new business). |
| `subscription_plan.unarchived` | An archived subscription plan was re-opened to new business. |
| `webhook.setup` | Verification call sent when an endpoint is registered or its URL is updated. `entityType` is `webhook`; `object` is the (secret-free) endpoint config. |

The ten catalogue events (`one_off_product.*` and `subscription_plan.*`) ship
dedicated typed event DTOs, just like the order/subscription/refund/checkout
events. `WebhookEventFactory` hydrates each one's `object` — byte-identical to
the corresponding `GET` body — straight into the matching resource, with **no
follow-up API call**:

| Event | Typed DTO | Hydrated resource |
|-------|-----------|-------------------|
| `one_off_product.update_submitted` | `OneOffProductUpdateSubmitted` | `$event->oneOffProduct` (`OneOffProduct`) |
| `one_off_product.update_approved` | `OneOffProductUpdateApproved` | `$event->oneOffProduct` |
| `one_off_product.update_rejected` | `OneOffProductUpdateRejected` | `$event->oneOffProduct` |
| `one_off_product.archived` | `OneOffProductArchived` | `$event->oneOffProduct` |
| `one_off_product.unarchived` | `OneOffProductUnarchived` | `$event->oneOffProduct` |
| `subscription_plan.update_submitted` | `SubscriptionPlanUpdateSubmitted` | `$event->subscriptionPlan` (`SubscriptionPlan`) |
| `subscription_plan.update_approved` | `SubscriptionPlanUpdateApproved` | `$event->subscriptionPlan` |
| `subscription_plan.update_rejected` | `SubscriptionPlanUpdateRejected` | `$event->subscriptionPlan` |
| `subscription_plan.archived` | `SubscriptionPlanArchived` | `$event->subscriptionPlan` |
| `subscription_plan.unarchived` | `SubscriptionPlanUnarchived` | `$event->subscriptionPlan` |

Each DTO also exposes the resource id (`$event->oneOffProductId` /
`$event->subscriptionPlanId`) and `$event->testmode` at the top level, and
carries a `VATLY_EVENT_NAME` constant for matching (all live under
`Vatly\API\Webhooks\Events\`).

```php
$event = $factory->createFromWebhook($webhook);

if ($event instanceof OneOffProductUpdateApproved) {
    // The approved product, already hydrated — no extra GET needed.
    echo $event->oneOffProduct->name;
    echo $event->oneOffProduct->basePrice->value;
}

if ($event instanceof SubscriptionPlanArchived) {
    echo $event->subscriptionPlan->archivedAt; // non-null
}
```

### The `webhook.setup` event

When you register a webhook endpoint (or change its URL), Vatly sends a signed
`webhook.setup` event to confirm the endpoint is reachable. It is delivered as a normal
webhook — same envelope, same signature, same `Vatly-Event-Id` header — so there is
**nothing special to parse**: `Webhook::parse()` returns an ordinary `WebhookPayload`.
Just acknowledge it with a `2xx` and take no action.

For strongly-typed handling, this package ships a
[`WebhookSetupReceived`](../src/API/Webhooks/Events/WebhookSetupReceived.php) event DTO
(`Vatly\API\Webhooks\Events\WebhookSetupReceived`) that carries the webhook envelope
(`id`, `resource`, `eventName`, `entityType`, `entityId`, `testmode`, `createdAt`,
`object`). Build it from a `WebhookReceived` via `WebhookSetupReceived::fromWebhook()`;
`object` is the (secret-free) endpoint config and may be empty.

---

## The WebhookEvent resource

Every delivery carries a [`WebhookEvent`](../src/API/Resources/WebhookEvent.php) JSON object in the request body. This is the same shape returned by `GET /v1/webhook-events/:id`.

### Properties

| Name | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique identifier for the webhook event (`webhook_event_...`). |
| `resource` | `string` | Always `webhook_event`. |
| `eventName` | `string` | One of the events listed above (e.g. `order.paid`). |
| `entityType` | `string` | Type of the related resource (e.g. `order`, `refund`, `subscription`). |
| `entityId` | `string` | ID of the related resource (e.g. `order_Hn5xWqVfKm8RjTgYbUcP`). |
| `object` | `object\|null` | The full resource payload at the time of the event. Shape depends on `entityType`. |
| `createdAt` | `string` | When the event occurred (ISO 8601). |
| `testmode` | `bool` | Whether this event was generated in test mode. |
| `links` | `object` | HATEOAS links — `links.self.href` points to this webhook event. |

### Example payload

```json
{
    "id": "webhook_event_Qk8pRtSvWm2NjLhYcZaE",
    "resource": "webhook_event",
    "eventName": "order.paid",
    "entityType": "order",
    "entityId": "order_Hn5xWqVfKm8RjTgYbUcP",
    "object": {
        "id": "order_Hn5xWqVfKm8RjTgYbUcP",
        "resource": "order",
        "status": "paid",
        "total": { "value": "29.99", "currency": "EUR" }
    },
    "createdAt": "2026-01-11T10:50:50+02:00",
    "testmode": true,
    "links": {
        "self": {
            "href": "https://api.vatly.com/v1/webhook-events/webhook_event_Qk8pRtSvWm2NjLhYcZaE",
            "type": "application/json"
        }
    }
}
```

---

## Delivery headers

Each webhook request includes two Vatly-specific headers.

| Header | Description |
| --- | --- |
| `Vatly-Signature` | Structured signature value: `t=<unix_seconds>,v1=<hex_hmac_sha256>`. Verify this before trusting the payload. |
| `Vatly-Event-Id` | The `id` of the underlying webhook event. Stable across retry attempts — use it as your idempotency / dedup key. |

The signature scheme prefix (`v1=`) leaves room for future algorithm versions; receivers that verify against `v1` will keep working if additional versions appear alongside it.

---

## Handling webhooks

The SDK ships [`Webhook::parse()`](../src/API/Webhooks/Webhook.php) — a one-shot helper that verifies the signature, decodes the JSON, and returns a typed [`WebhookPayload`](../src/API/Webhooks/WebhookPayload.php) ready to dispatch on.

Verification is performed against the **raw request body bytes**. JSON that is parsed and re-encoded will not match the signature — read the body directly (e.g. `file_get_contents('php://input')`) before any framework deserialises it.

```php
use Vatly\API\Exceptions\InvalidSignatureException;
use Vatly\API\Webhooks\Webhook;

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_VATLY_SIGNATURE'] ?? '';
$secret    = getenv('VATLY_WEBHOOK_SECRET');

try {
    $event = Webhook::parse($payload, $signature, $secret);
} catch (InvalidSignatureException $e) {
    http_response_code(401);
    exit('Invalid signature');
}

// Dedupe with Vatly-Event-Id (stable across retry attempts).
$eventId = $_SERVER['HTTP_VATLY_EVENT_ID'] ?? $event->id;
if (alreadyProcessed($eventId)) {
    http_response_code(200);
    exit;
}

match ($event->eventName) {
    'order.paid'         => handleOrderPaid($event),
    'refund.completed'   => handleRefundCompleted($event),
    'checkout.expired'   => handleCheckoutExpired($event),
    default              => null,
};

markProcessed($eventId);
http_response_code(200);
```

`Webhook::parse()` throws `Vatly\API\Exceptions\InvalidSignatureException` when the signature header is malformed, the timestamp is outside the tolerance window, or the HMAC does not match. It throws `InvalidArgumentException` when the body is not valid JSON or is missing required fields.

### Typed event DTOs

`Webhook::parse()` returns the raw, generic [`WebhookPayload`](../src/API/Webhooks/WebhookPayload.php). For consumers that prefer a strongly-typed, per-event object, this package also owns immutable event DTOs under [`Vatly\API\Webhooks\Events`](../src/API/Webhooks/Events). Each DTO:

- exposes a `VATLY_EVENT_NAME` constant (a [`WebhookEventName`](../src/API/Types/WebhookEventName.php) value) for matching;
- carries `from*()` factory methods — `fromApiOrder()`, `fromApiRefund()`, `fromApiChargeback()`, `fromApiSubscription()` to build from an enriched API resource, and/or `fromWebhook(WebhookReceived $webhook)` to build from the raw payload;
- carries money as [`Money`](../src/API/Types/Money.php) values (decimal-string + currency) and the per-rate VAT breakdown as a [`TaxSummaryCollection`](../src/API/Types/TaxSummaryCollection.php). Flatten to integer cents at your persistence edge via [`Money::toCents()`](../src/API/Types/Money.php).

```php
use Vatly\API\Resources\Order;
use Vatly\API\Webhooks\Events\OrderPaid;

// $order is a fully-hydrated API Order resource (e.g. from $vatly->orders->get(...))
$event = OrderPaid::fromApiOrder($order);

$event->total;             // Vatly\API\Types\Money
$event->total->value;      // decimal string, e.g. "49.99"
$event->total->currency;   // e.g. "USD"
$event->total->toCents();  // int cents, e.g. 4999
$event->taxSummary;        // Vatly\API\Types\TaxSummaryCollection
$event->lines;             // Vatly\API\Types\OrderLineData[]
$event->testmode;          // bool — test (true) vs live (false), mirrored from the source record
```

Every business event (order/subscription/refund/chargeback/checkout) carries `testmode`, sourced from the enriched resource (`$resource->testmode`) or the webhook envelope (`$webhook->testmode`). Persist it alongside the record so test and live data stay segregated and the matching API key can be selected per record.

These DTOs are the canonical, framework-agnostic event shapes that higher-level integrations (e.g. `vatly-fluent-php`) build on. The line-item DTO lives at [`Vatly\API\Types\OrderLineData`](../src/API/Types/OrderLineData.php); its `basePrice`/`total`/`subtotal` are `Money` too.

The full set of incoming webhook payloads is also described in the [webhooks OpenAPI spec](https://docs.vatly.com/openapi.yaml) under the top-level `webhooks:` section — one entry per `WebhookEventName`, each referencing the `WebhookDelivery` envelope.

### Replay-window tolerance

The signed timestamp (`t=...`) lets receivers reject stale deliveries. By default signatures more than **300 seconds** old are rejected. If you need a custom window — for example when replaying captured fixtures in a test suite — instantiate [`WebhookSignatureValidator`](../src/API/Webhooks/WebhookSignatureValidator.php) directly:

```php
use Vatly\API\Webhooks\WebhookSignatureValidator;

$validator = new WebhookSignatureValidator($secret, toleranceSeconds: 60);
$validator->verify($payload, $signature);
```

Keep the default in production. A tighter window makes a leaked signature less useful; a much wider window weakens the replay-defense guarantee.

### Lower-level access

If you only need signature verification (e.g. handling the decoded body yourself, or operating on a non-standard payload shape), use [`WebhookSignatureValidator`](../src/API/Webhooks/WebhookSignatureValidator.php) directly. It exposes `verify()`, `isValid()`, and `calculateSignature()`, plus header-name constants:

```php
WebhookSignatureValidator::SIGNATURE_HEADER_NAME; // 'Vatly-Signature'
WebhookSignatureValidator::EVENT_ID_HEADER_NAME;  // 'Vatly-Event-Id'
```

---

## Best practices

1. **Always verify signatures** before processing webhook payloads.
2. **Verify against the raw body**, not parsed-and-reserialised JSON.
3. **Dedupe with `Vatly-Event-Id`** — retries reuse the same event id, while the signature deliberately rotates per attempt.
4. **Return 200 quickly** to avoid timeout retries. Offload long-running work to a queue.
5. **Log webhook events** for debugging and auditing.
