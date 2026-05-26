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
| `object` | `object` | The full resource payload at the time of the event. Shape depends on `entityType`. |
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

## Verifying signatures

Always verify the `Vatly-Signature` header before processing a webhook. The SDK ships [`WebhookSignatureValidator`](../src/API/Webhooks/WebhookSignatureValidator.php) for exactly that.

Verification is performed against the **raw request body bytes**. JSON that is parsed and re-encoded will not match the signature — read the body directly (e.g. `file_get_contents('php://input')`) before any framework deserialises it.

```php
use Vatly\API\Exceptions\InvalidSignatureException;
use Vatly\API\Webhooks\WebhookSignatureValidator;

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_VATLY_SIGNATURE'] ?? '';
$secret    = getenv('VATLY_WEBHOOK_SECRET');

$validator = new WebhookSignatureValidator($secret);

try {
    $validator->verify($payload, $signature);
} catch (InvalidSignatureException $e) {
    http_response_code(401);
    exit('Invalid signature');
}

$event = json_decode($payload);

match ($event->eventName) {
    'order.paid'         => handleOrderPaid($event),
    'refund.completed'   => handleRefundCompleted($event),
    'checkout.expired'   => handleCheckoutExpired($event),
    default              => null,
};

http_response_code(200);
```

If you prefer a non-throwing check, use `isValid()`:

```php
if (! $validator->isValid($payload, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}
```

### Replay-window tolerance

The signed timestamp (`t=...`) lets receivers reject stale deliveries. By default `WebhookSignatureValidator` accepts signatures up to **300 seconds** old; anything outside that window throws `InvalidSignatureException`.

Override the window via the `toleranceSeconds` constructor argument when you have a specific need (e.g. running against recorded fixtures in a test suite):

```php
$validator = new WebhookSignatureValidator($secret, toleranceSeconds: 60);
```

We recommend keeping the default in production. A tighter window makes a leaked signature less useful; a much wider window weakens the replay-defense guarantee.

### Header name constants

Header names are exposed as class constants so you don't have to hardcode the strings.

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
