# Checkouts

Checkouts create hosted payment pages for your customers. When a checkout completes successfully, an Order is created.

> **Authoritative field reference.** This page covers the common cases. The complete, canonical request/response schema — every accepted field, its type, and its validation rules — lives in the [OpenAPI spec](https://docs.vatly.com/openapi.yaml) (see the `Checkout`, `CheckoutProduct`, and `Money` schemas). When in doubt about what a field is named or whether it exists, trust the spec, not an example.

## The Checkout Resource

Below you'll find all properties for the Vatly Checkout resource.

### Properties

| Name | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique identifier for the checkout (`checkout_...`). |
| `status` | `string` | The status of the checkout: `created`, `paid`, `canceled`, `failed`, or `expired`. |
| `merchantId` | `string` | Your merchant ID. |
| `orderId` | `string \| null` | Order ID (available after successful payment). |
| `testmode` | `bool` | Whether this is a test checkout. |
| `redirectUrlSuccess` | `string` | Success redirect URL. |
| `redirectUrlCanceled` | `string` | Cancel redirect URL. |
| `metadata` | `array` | Your custom metadata. |
| `locale` | `string \| null` | The language the hosted checkout is presented in (`en`, `de`, `fr`, `nl`, `es`, `it`, `pt`, `pl`), echoed back as the folded language. `null` means none was specified and the checkout picks a language from the shopper's browser. |
| `expiresAt` | `string \| null` | Expiration timestamp (ISO 8601). |
| `createdAt` | `string` | Creation timestamp (ISO 8601). |

---

## Create a checkout

`POST /v1/checkouts`



Create a new hosted checkout for your customer.

### Required attributes

| Name | Type | Description |
| --- | --- | --- |
| `products` | `array` | An array of product items. See [Product items](#product-items) below for the accepted per-item fields. |
| `redirectUrlSuccess` | `string` | The URL to redirect after successful payment. |
| `redirectUrlCanceled` | `string` | The URL to redirect if the customer cancels. |

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `customerId` | `string` | Existing customer ID to associate with this checkout. |
| `metadata` | `array` | Your custom metadata to store with the checkout. |
| `locale` | `string` | Language to present the hosted checkout in — set this when you already know the customer's language. Accepts a bare language code (`de`), a BCP 47 tag (`de-AT`), or a POSIX/ISO 15897 locale (`de_DE`); all three fold to the language. Supported: `en`, `de`, `fr`, `nl`, `es`, `it`, `pt`, `pl` (anything else returns `422`). Omit (or `null`) to detect the language from the shopper's `Accept-Language` header, falling back to English. The response echoes back the folded language. |




```php
$checkout = $vatly->checkouts->create([
    'products' => [
        [
            'id' => 'subscription_plan_abc123',
            'quantity' => 1,
            'trialDays' => 14,
        ]
    ],
    'redirectUrlSuccess' => 'https://yourapp.com/success',
    'redirectUrlCanceled' => 'https://yourapp.com/canceled',
    'customerId' => 'customer_xyz789',
    'locale' => 'de', // optional; present the hosted checkout in German
    'metadata' => [
        'user_id' => '12345',
    ],
]);

echo $checkout->locale; // 'de'

// Redirect to hosted checkout
$checkoutUrl = $checkout->getCheckoutUrl();
```

If you want to provide your own idempotency key for this request, pass it in the optional second argument:

```php
$checkout = $vatly->checkouts->create([
    'products' => [
        [
            'id' => 'subscription_plan_abc123',
            'quantity' => 1,
        ],
    ],
    'redirectUrlSuccess' => 'https://yourapp.com/success',
    'redirectUrlCanceled' => 'https://yourapp.com/canceled',
], [
    'idempotencyKey' => 'checkout-create-123',
]);
```

If you omit `idempotencyKey`, the SDK generates an `Idempotency-Key` header automatically.

### Product items

Each entry in `products` references a product you have **already created in the [Vatly dashboard](https://my.vatly.com)** — there is no API endpoint to create products on the fly. The `id` is either a `one_off_product_…` or `subscription_plan_…` id.

To charge an amount other than the product's dashboard price, set a `price` override (a `Money` object). For the full list of accepted item fields and their validation rules, see the `CheckoutProduct` schema in the [OpenAPI spec](https://docs.vatly.com/openapi.yaml).

```php
// Override the price of a pre-configured product at checkout time.
$checkout = $vatly->checkouts->create([
    'products' => [
        [
            'id' => 'one_off_product_Vr8kQdFhSrG4Y3DnfsdqH',
            'quantity' => 2,
            'price' => [
                'value' => '49.99',
                'currency' => 'EUR',
            ],
        ],
    ],
    'redirectUrlSuccess' => 'https://yourapp.com/success',
    'redirectUrlCanceled' => 'https://yourapp.com/canceled',
]);
```

```json
{
  "id": "checkout_abc123",
  "status": "created",
  "merchantId": "merchant_xyz",
  "orderId": null,
  "testmode": false,
  "redirectUrlSuccess": "https://yourapp.com/success",
  "redirectUrlCanceled": "https://yourapp.com/canceled",
  "metadata": {"user_id": "12345"},
  "createdAt": "2024-12-14T13:32:24.000Z",
  "_links": {
    "checkoutUrl": "https://pay.vatly.com/checkout_abc123"
  }
}
```



---

## Retrieve a checkout

`GET /v1/checkouts/:id`



Retrieve a checkout by its ID.




```php
$checkout = $vatly->checkouts->get('checkout_abc123');

echo $checkout->id;
echo $checkout->status;
echo $checkout->getCheckoutUrl();

if ($checkout->isPaid()) {
    $orderId = $checkout->orderId;
}
```



---

## List all checkouts

`GET /v1/checkouts`



Retrieve a paginated list of all your checkouts.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `limit` | `integer` | The number of checkouts to return (default: 10, max: 100). |
| `startingAfter` | `string` | A cursor for pagination. Returns results after this checkout ID. |




```php
// Get all checkouts (paginated)
$checkouts = $vatly->checkouts->list();

foreach ($checkouts as $checkout) {
    echo $checkout->id . ': ' . $checkout->status;
}

// Pagination
$checkouts = $vatly->checkouts->list([
    'limit' => 25,
    'startingAfter' => 'checkout_last_id',
]);
```



---

## Checkout statuses

| Status | Description |
|--------|-------------|
| `created` | Checkout is active, awaiting payment |
| `paid` | Payment successful, order created |
| `canceled` | Customer canceled the checkout |
| `failed` | Payment failed |
| `expired` | Checkout expired without completion |

---

## Helper methods



The Checkout object provides convenient helper methods.




```php
$checkout->isPaid();         // true if status is 'paid'
$checkout->isCanceled();     // true if status is 'canceled'
$checkout->isExpired();      // true if status is 'expired'
$checkout->getCheckoutUrl(); // Hosted checkout page URL
$checkout->getOrderId();     // Order ID (null if not paid)
```
