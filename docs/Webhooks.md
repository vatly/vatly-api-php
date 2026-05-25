# Webhooks

Vatly sends webhooks to notify your application when events happen, like successful checkouts, subscription renewals, or chargebacks.

## Webhook Events

| Event | Description |
|-------|-------------|
| `checkout.paid` | Checkout completed successfully |
| `checkout.failed` | Checkout payment failed |
| `checkout.expired` | Checkout expired |
| `subscription.created` | New subscription started |
| `subscription.renewed` | Subscription renewed |
| `subscription.canceled` | Subscription canceled |
| `subscription.ended` | Subscription ended |
| `order.paid` | Order payment received |
| `refund.created` | Refund initiated |
| `refund.completed` | Refund completed |
| `chargeback.created` | Chargeback received |
| `chargeback.won` | Chargeback dispute won |
| `chargeback.lost` | Chargeback dispute lost |

---

## Handling webhooks



The SDK provides a helper to parse and verify incoming webhooks.

### Webhook payload

| Name | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique webhook event ID. |
| `resource` | `string` | Always `webhook_event`. |
| `eventName` | `string` | Event type (e.g., `order.paid`). |
| `entityType` | `string` | Type of the resource this event relates to (e.g., `order`). |
| `entityId` | `string` | ID of the resource this event relates to. |
| `object` | `object\|null` | The full resource payload at the time of the event. |
| `createdAt` | `string\|null` | When the event occurred (ISO 8601). |




```php
use Vatly\API\VatlyApiClient;
use Vatly\API\Webhooks\Webhook;

$vatly = new VatlyApiClient();
$vatly->setApiKey('live_your_api_key_here');

// Get the raw webhook payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_VATLY_SIGNATURE'] ?? '';
$secret = 'your_webhook_secret';

// Parse and verify the webhook
$event = Webhook::parse($payload, $signature, $secret);

switch ($event->eventName) {
    case 'checkout.paid':
        $checkout = $event->object;
        // Fulfill the order
        break;

    case 'subscription.renewed':
        $subscription = $event->object;
        // Extend access
        break;

    case 'chargeback.created':
        $chargeback = $event->object;
        // Suspend account, notify team
        break;
}

// Return 200 OK
http_response_code(200);
```



---

## Signature verification



Always verify webhook signatures to ensure the request came from Vatly.




```php
use Vatly\API\Webhooks\Webhook;
use Vatly\API\Exceptions\InvalidSignatureException;

try {
    $event = Webhook::parse($payload, $signature, $secret);
    // Signature valid, process event
} catch (InvalidSignatureException $e) {
    // Invalid signature, reject request
    http_response_code(401);
    exit('Invalid signature');
}
```



---

## Laravel integration



For Laravel applications, you can use controller middleware and route handling.




```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Vatly\API\Webhooks\Webhook;

class VatlyWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = Webhook::parse(
            $request->getContent(),
            $request->header('Vatly-Signature'),
            config('services.vatly.webhook_secret')
        );

        match ($event->eventName) {
            'checkout.paid' => $this->handleCheckoutPaid($event->object),
            'subscription.canceled' => $this->handleCanceled($event->object),
            default => null,
        };

        return response()->json(['received' => true]);
    }
}
```

```php
Route::post('/webhooks/vatly', [VatlyWebhookController::class, 'handle'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
```



---

## Best practices

1. **Always verify signatures** before processing webhooks
2. **Return 200 quickly** to avoid timeout retries
3. **Process asynchronously** for long-running tasks (queue jobs)
4. **Handle duplicates** using the event ID (webhooks may be retried)
5. **Log webhook events** for debugging and auditing
