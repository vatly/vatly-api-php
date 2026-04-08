# Checkouts

Checkouts create hosted payment pages for your customers. When a checkout completes successfully, an Order is created.

## The Checkout Resource

Below you'll find all properties for the Vatly Checkout resource.

### Properties

| Name | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique identifier for the checkout (`chk_...`). |
| `status` | `string` | The status of the checkout: `created`, `paid`, `canceled`, `failed`, or `expired`. |
| `merchantId` | `string` | Your merchant ID. |
| `orderId` | `string|null` | Order ID (available after successful payment). |
| `testmode` | `bool` | Whether this is a test checkout. |
| `redirectUrlSuccess` | `string` | Success redirect URL. |
| `redirectUrlCanceled` | `string` | Cancel redirect URL. |
| `metadata` | `array` | Your custom metadata. |
| `expiresAt` | `string|null` | Expiration timestamp (ISO 8601). |
| `createdAt` | `string` | Creation timestamp (ISO 8601). |

---

## Create a checkout

`POST /v1/checkouts`



Create a new hosted checkout for your customer.

### Required attributes

| Name | Type | Description |
| --- | --- | --- |
| `products` | `array` | An array of product objects with `id`, optional `quantity`, and optional `trialDays`. |
| `redirectUrlSuccess` | `string` | The URL to redirect after successful payment. |
| `redirectUrlCanceled` | `string` | The URL to redirect if the customer cancels. |

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `customerId` | `string` | Existing customer ID to associate with this checkout. |
| `metadata` | `array` | Your custom metadata to store with the checkout. |




```php
$checkout = $vatly->checkouts->create([
    'products' => [
        [
            'id' => 'plan_abc123',
            'quantity' => 1,
            'trialDays' => 14,
        ]
    ],
    'redirectUrlSuccess' => 'https://yourapp.com/success',
    'redirectUrlCanceled' => 'https://yourapp.com/canceled',
    'customerId' => 'cus_xyz789',
    'metadata' => [
        'user_id' => '12345',
    ],
]);

// Redirect to hosted checkout
$checkoutUrl = $checkout->getCheckoutUrl();
```

If you want to provide your own idempotency key for this request, pass it in the optional second argument:

```php
$checkout = $vatly->checkouts->create([
    'products' => [
        [
            'id' => 'plan_abc123',
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

```json
{
  "id": "chk_abc123",
  "status": "created",
  "merchantId": "merchant_xyz",
  "orderId": null,
  "testmode": false,
  "redirectUrlSuccess": "https://yourapp.com/success",
  "redirectUrlCanceled": "https://yourapp.com/canceled",
  "metadata": {"user_id": "12345"},
  "createdAt": "2024-12-14T13:32:24.000Z",
  "_links": {
    "checkoutUrl": "https://pay.vatly.com/chk_abc123"
  }
}
```



---

## Retrieve a checkout

`GET /v1/checkouts/:id`



Retrieve a checkout by its ID.




```php
$checkout = $vatly->checkouts->get('chk_abc123');

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
    'startingAfter' => 'chk_last_id',
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
