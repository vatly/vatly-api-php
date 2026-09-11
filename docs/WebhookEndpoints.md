# Webhook Endpoints

A webhook endpoint is the HTTPS URL Vatly POSTs event deliveries to. You register
one from code (or infrastructure-as-code) instead of the dashboard. A storefront
can have **up to five endpoints per mode** (test and live are determined by the
API token); URLs must be unique within the storefront and mode. Registering a
duplicate URL or a sixth endpoint is rejected with `422`.

Each endpoint carries an `enabledEvents` subscription set — the public event names
it receives (see [`WebhookSubscriptionEventName`](../src/API/Types/WebhookSubscriptionEventName.php)).
An empty set makes the endpoint dormant; the `webhook.setup` verification event is
never subscribable and is always sent when Vatly verifies the endpoint.

The signing `secret` you provide is **write-only**: it is sent on create/update
but is never returned in any response. Store the value you send — you use it to
verify the `Vatly-Signature` HMAC on deliveries (see [Webhooks](/docs/Webhooks.md)).

## The WebhookEndpoint Resource

Below you'll find all properties for the Vatly WebhookEndpoint resource.

### Properties

| Name | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique identifier for the endpoint (`webhook_...`). |
| `resource` | `string` | Resource type, always `webhook_endpoint`. |
| `testmode` | `bool` | Whether this endpoint receives test-mode events. |
| `url` | `string` | The HTTPS URL deliveries are POSTed to. |
| `enabledEvents` | `string[]` | The endpoint's persisted subscription names. An empty array means no domain events are delivered (dormant); `webhook.setup` is still sent. |
| `createdAt` | `string` | Creation timestamp (ISO 8601). |
| `links` | `WebhookEndpointLinks` | HATEOAS links (`self`). |

> The signing `secret` is never present on the resource — it is write-only.

---

## Register a webhook endpoint

`POST /v1/webhook-endpoints`



Register the endpoint for the mode determined by the API token. Vatly sends a
`webhook.setup` verification ping to the URL and validates its SSL certificate;
if either fails the request is rejected. A storefront may have up to five
endpoints per mode and URLs must be unique within the storefront and mode; a
duplicate URL or a sixth endpoint is rejected with `422`.

### Required attributes

| Name | Type | Description |
| --- | --- | --- |
| `url` | `string` | Publicly reachable HTTPS URL with a valid SSL certificate. `localhost`/loopback addresses are not allowed. |
| `secret` | `string` | Signing secret (min 10 chars). Write-only — keep this value, the API never returns it. |

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `enabledEvents` | `string[]` | The events delivered to this endpoint (`WebhookSubscriptionEventName` values). **Omit** it and Vatly subscribes to every event available at registration (not updated automatically afterwards); send `[]` for a dormant endpoint. `webhook.setup` is not selectable. |




```php
use Vatly\API\Types\WebhookSubscriptionEventName;

$endpoint = $vatly->webhookEndpoints->create([
    'url' => 'https://merchant.example/webhooks/vatly',
    'secret' => getenv('VATLY_WEBHOOK_SECRET'), // min 10 chars, keep it — never returned
    'enabledEvents' => [
        WebhookSubscriptionEventName::ORDER_PAID,
        WebhookSubscriptionEventName::REFUND_COMPLETED,
    ],
]);

echo $endpoint->id;  // webhook_...
echo $endpoint->url;
print_r($endpoint->enabledEvents);
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



List the endpoints for the token's mode. A storefront may have up to five
endpoints per mode, so this returns up to five endpoints.




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
`enabledEvents` subscription set. A new URL is revalidated for reachability and
SSL just like on creation. Sending an empty body is a no-op that returns the
current endpoint.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `url` | `string` | New HTTPS delivery URL. |
| `secret` | `string` | New signing secret (min 10 chars). Write-only — keep the value. |
| `enabledEvents` | `string[]` | Replaces the **complete** subscription set (`WebhookSubscriptionEventName` values). Omit it to preserve the current subscriptions; send `[]` to make the endpoint dormant. New event names are never added automatically. |




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
