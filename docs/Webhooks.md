# Webhooks

Vatly sends webhooks to notify your application when events happen — for example, an order being paid, a refund completing, or a subscription being canceled.

## Webhook events

The `eventName` field on a delivery identifies what happened. See [`Vatly\API\Types\WebhookEvent`](../src/API/Types/WebhookEvent.php) for the constants.

| Event | Description |
|-------|-------------|
| `order.paid` | Order payment was successful. |
| `order.canceled` | Order was canceled. |
| `order.chargeback_received` | Chargeback was received for an order. |
| `order.chargeback_reversed` | Chargeback was reversed. |
| `refund.completed` | Refund was processed successfully. |
| `refund.failed` | Refund processing failed. |
| `refund.canceled` | Refund was canceled. |
| `subscription.started` | Subscription was started. |
| `subscription.canceled_immediately` | Subscription was canceled immediately. |
| `subscription.canceled_with_grace_period` | Subscription was canceled, customer keeps access until the period ends. |
| `subscription.cancellation_grace_period_completed` | Grace period after cancellation ended. |
| `checkout.expired` | Checkout session expired. |

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
