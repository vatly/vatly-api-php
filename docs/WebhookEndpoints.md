# Webhook Endpoints

A webhook endpoint is the HTTPS URL Vatly POSTs event deliveries to — registered
from code (or infrastructure-as-code) rather than the dashboard. A storefront can
have **up to five endpoints per mode** (test and live are set by the API token),
each with a URL unique within that storefront and mode. A duplicate URL or a sixth
endpoint is rejected with `422`.

Each endpoint has its own `enabledEvents` set — the public event names it receives
(see [`WebhookSubscriptionEventName`](../src/API/Types/WebhookSubscriptionEventName.php)).
An empty set makes it dormant; `webhook.setup` is never subscribable and is always
sent when Vatly verifies the endpoint.

The signing `secret` is **write-only**: sent on create/update, never returned.
Store the value you send — you use it to verify the `Vatly-Signature` HMAC on
deliveries (see [Webhooks](/docs/Webhooks.md)).

## The WebhookEndpoint Resource

Below you'll find all properties for the Vatly WebhookEndpoint resource.

### Properties

| Name | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique identifier for the endpoint (`webhook_...`). |
| `resource` | `string` | Resource type, always `webhook_endpoint`. |
| `testmode` | `bool` | Whether this endpoint receives test-mode events. |
| `url` | `string` | The HTTPS URL deliveries are POSTed to. |
| `enabledEvents` | `string[]` | The event names this endpoint is subscribed to. An empty array means dormant (no domain events); `webhook.setup` is still sent. |
| `createdAt` | `string` | Creation timestamp (ISO 8601). |
| `links` | `WebhookEndpointLinks` | HATEOAS links (`self`). |

---

## Register a webhook endpoint

`POST /v1/webhook-endpoints`



Register an endpoint for the token's mode. Vatly sends a `webhook.setup`
verification ping and validates the URL's SSL certificate; if either fails,
registration is rejected with `422`.

### Required attributes

| Name | Type | Description |
| --- | --- | --- |
| `url` | `string` | Publicly reachable HTTPS URL with a valid SSL certificate. `localhost`/loopback addresses are not allowed. |
| `secret` | `string` | Signing secret (min 10 chars). Write-only — keep it, the API never returns it. |

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `enabledEvents` | `string[]` | The events to deliver (`WebhookSubscriptionEventName` values). Omit to subscribe to every event available at registration (not updated afterwards); send `[]` for a dormant endpoint. `webhook.setup` is not selectable. |




```php
use Vatly\API\Types\WebhookSubscriptionEventName;

$endpoint = $vatly->webhookEndpoints->create([
    'url' => 'https://merchant.example/webhooks/vatly',
    'secret' => getenv('VATLY_WEBHOOK_SECRET'),
    'enabledEvents' => [
        WebhookSubscriptionEventName::ORDER_PAID,
        WebhookSubscriptionEventName::REFUND_COMPLETED,
    ],
]);

echo $endpoint->id;  // webhook_...
```



---

## Retrieve a webhook endpoint

`GET /v1/webhook-endpoints/:id`



Retrieve an endpoint by its ID. The signing secret is never included.




```php
$endpoint = $vatly->webhookEndpoints->get('webhook_QdEpFhdSrG4Y3DnfsdqsH');

echo $endpoint->url;
```



---

## List webhook endpoints

`GET /v1/webhook-endpoints`



List all endpoints for the token's mode.




```php
$endpoints = $vatly->webhookEndpoints->page();

foreach ($endpoints as $endpoint) {
    echo $endpoint->url;
}
```



---

## Update a webhook endpoint

`PATCH /v1/webhook-endpoints/:id`



Repoint the endpoint (`url`), rotate the signing `secret`, and/or replace its
`enabledEvents` set. A new URL is revalidated for reachability and SSL just like
on creation. An empty body is a no-op that returns the current endpoint.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `url` | `string` | New HTTPS delivery URL. |
| `secret` | `string` | New signing secret (min 10 chars). Write-only. |
| `enabledEvents` | `string[]` | Replaces the **complete** subscription set (`WebhookSubscriptionEventName` values). Omit to preserve the current set; send `[]` to make the endpoint dormant. |




```php
use Vatly\API\Types\WebhookSubscriptionEventName;

$endpoint = $vatly->webhookEndpoints->update('webhook_QdEpFhdSrG4Y3DnfsdqsH', [
    'url' => 'https://merchant.example/webhooks/vatly-v2',
    'enabledEvents' => [
        WebhookSubscriptionEventName::CHECKOUT_PAID,
        WebhookSubscriptionEventName::ORDER_PAID,
    ],
]);
```

If you already have a `WebhookEndpoint` resource instance:

```php
$endpoint->update([
    'secret' => getenv('VATLY_WEBHOOK_SECRET_NEXT'),
]);
```



---

## Delete a webhook endpoint

`DELETE /v1/webhook-endpoints/:id`



Delete an endpoint. Vatly stops sending deliveries to it immediately. To receive
events again, register a new endpoint. Returns no content.




```php
$vatly->webhookEndpoints->delete('webhook_QdEpFhdSrG4Y3DnfsdqsH');

// Or, from a resource instance:
$endpoint->delete();
```
