# Orders

Orders are created automatically when a checkout completes successfully or when a subscription renews.

## The Order Resource

Below you'll find all properties for the Vatly Order resource.

### Properties

| Name | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique identifier for the order (`order_...`). |
| `resource` | `string` | Always `order`. |
| `customerId` | `string \| null` | The customer ID, or `null` for orders with no associated customer. |
| `status` | `string` | The order status (see [Order statuses](#order-statuses)). |
| `cancellationReason` | `string \| null` | Why the order was canceled: `payment_failure` when payment recovery for this unpaid order was exhausted. `null` unless the status is `canceled`, and `null` for orders canceled before this field existed. |
| `total` | `Money` | Total amount including taxes. |
| `subtotal` | `Money` | Subtotal amount before taxes. |
| `reversedSubtotal` | `Money` | Amount of the captured payment returned to the customer, before taxes, via refund or chargeback (settled). |
| `refundableSubtotal` | `Money` | Remaining amount that can still be refunded, before taxes. |
| `taxSummary` | `TaxSummaryCollection` | Tax breakdown by rate. |
| `lines` | `array` | The order lines. Use `$order->lines()` for a collection of `OrderLine` objects. |
| `customerDetails` | `Address` | Customer billing details. |
| `paymentMethod` | `string \| null` | The payment method used, for example `ideal` or `creditcard`. |
| `invoiceNumber` | `string \| null` | The invoice number, assigned after payment. |
| `testmode` | `bool` | Whether this is a test order. |
| `metadata` | `array` | Your custom metadata. |
| `createdAt` | `string` | Creation timestamp (ISO 8601). |
| `links` | `OrderLinks` | Links to this order, the customer (`null` for orders with no associated customer) and the customer invoice. |

---

## Retrieve an order

`GET /v1/orders/:id`



Retrieve an order by its ID.




```php
$order = $vatly->orders->get('order_abc123');

echo $order->status;
echo $order->total->value . ' ' . $order->total->currency;

if ($order->isPaid()) {
    echo 'Invoice: ' . $order->invoiceNumber;
}

if ($order->isCanceled()) {
    echo 'Canceled because: ' . ($order->cancellationReason ?? 'unknown');
}
```



---

## List all orders

`GET /v1/orders`



Retrieve a paginated list of all orders.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `startingAfter` | `string` | A cursor for pagination: return the page after this order ID. |
| `endingBefore` | `string` | A cursor for pagination: return the page before this order ID. |
| `limit` | `integer` | The number of orders to return (default: 10, max: 100). |




```php
$orders = $vatly->orders->page();

foreach ($orders as $order) {
    echo $order->id . ': ' . $order->total->value . ' ' . $order->total->currency;
}

// Pass the cursors and limit in order: startingAfter, endingBefore, limit
$orders = $vatly->orders->page('order_abc123', null, 25);
```



---

## Order statuses

| Status | Description |
|--------|-------------|
| `created` | Order has just been created |
| `pending` | Payment is being processed |
| `paid` | Payment successful |
| `canceled` | Order has been canceled |
| `expired` | Order has expired |

A failed payment does not change the order to a status of its own. You receive the `order.payment_failed` webhook while Vatly tries to recover the payment. If recovery is exhausted, the order is `canceled` with `cancellationReason: payment_failure`, and you receive `order.canceled`.

---

## Helper methods



The Order object provides convenient helper methods.




```php
$order->isCreated();   // true if status is 'created'
$order->isPending();   // true if status is 'pending'
$order->isPaid();      // true if status is 'paid'
$order->isCanceled();  // true if status is 'canceled'
$order->isExpired();   // true if status is 'expired'

$order->isReversed();          // true if any of the payment was returned to the customer
$order->isPartiallyReversed(); // true if some, but not all, of the payment was returned
$order->isFullyReversed();     // true if the full payment was returned
```
