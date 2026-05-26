# Orders

Orders are created automatically when a checkout completes successfully or when a subscription renews.

## The Order Resource

Below you'll find all properties for the Vatly Order resource.

### Properties

| Name | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique identifier for the order (`order_...`). |
| `status` | `string` | The status: `pending`, `paid`, or `failed`. |
| `customerId` | `string` | The customer ID. |
| `checkoutId` | `string \| null` | The checkout ID (for initial orders). |
| `subscriptionId` | `string \| null` | The subscription ID (for recurring orders). |
| `amount` | `integer` | Total amount in cents. |
| `currency` | `string` | Three-letter ISO currency code. |
| `taxAmount` | `integer` | Tax amount in cents. |
| `testmode` | `bool` | Whether this is a test order. |
| `metadata` | `array` | Your custom metadata. |
| `paidAt` | `string \| null` | When the order was paid (ISO 8601). |
| `createdAt` | `string` | Creation timestamp (ISO 8601). |

---

## Retrieve an order

`GET /v1/orders/:id`



Retrieve an order by its ID.




```php
$order = $vatly->orders->get('order_abc123');

echo $order->status;
echo $order->amount / 100 . ' ' . $order->currency;

if ($order->isPaid()) {
    echo 'Paid at: ' . $order->paidAt;
}
```



---

## List all orders

`GET /v1/orders`



Retrieve a paginated list of all orders.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `limit` | `integer` | The number of orders to return (default: 10, max: 100). |
| `startingAfter` | `string` | A cursor for pagination. |
| `customerId` | `string` | Filter by customer ID. |




```php
$orders = $vatly->orders->list();

foreach ($orders as $order) {
    echo $order->id . ': ' . ($order->amount / 100) . ' ' . $order->currency;
}

// Filter by customer
$orders = $vatly->orders->list([
    'customerId' => 'customer_abc123',
]);
```



---

## Order statuses

| Status | Description |
|--------|-------------|
| `pending` | Order is awaiting payment |
| `paid` | Payment successful |
| `failed` | Payment failed |

---

## Helper methods



The Order object provides convenient helper methods.




```php
$order->isPaid();      // true if status is 'paid'
$order->isPending();   // true if status is 'pending'
$order->isFailed();    // true if status is 'failed'
```
