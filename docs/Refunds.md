# Refunds

Refunds allow you to return money to your customers for orders.

## The Refund Resource

Below you'll find all properties for the Vatly Refund resource.

### Properties

| Name | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique identifier for the refund (`refund_...`). |
| `status` | `string` | The status: `pending`, `completed`, `failed`, or `canceled`. |
| `orderId` | `string` | The order ID being refunded. |
| `amount` | `integer` | Refund amount in cents. |
| `currency` | `string` | Three-letter ISO currency code. |
| `reason` | `string \| null` | Reason for the refund. |
| `testmode` | `bool` | Whether this is a test refund. |
| `metadata` | `array` | Your custom metadata. |
| `createdAt` | `string` | Creation timestamp (ISO 8601). |

---

## Create a refund

`POST /v1/refunds`



Create a refund for an order.

### Required attributes

| Name | Type | Description |
| --- | --- | --- |
| `orderId` | `string` | The ID of the order to refund. |

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `amount` | `integer` | Partial refund amount in cents (omit for full refund). |
| `reason` | `string` | Reason for the refund. |




```php
// Full refund
$refund = $vatly->refunds->create([
    'orderId' => 'order_abc123',
    'reason' => 'Customer requested refund',
]);

// Partial refund
$refund = $vatly->refunds->create([
    'orderId' => 'order_abc123',
    'amount' => 1000,  // Refund €10.00
]);

echo $refund->id;
echo $refund->status;
```



---

## Retrieve a refund

`GET /v1/refunds/:id`



Retrieve a refund by its ID.




```php
$refund = $vatly->refunds->get('refund_abc123');

echo $refund->status;
echo $refund->amount / 100 . ' ' . $refund->currency;
```



---

## List all refunds

`GET /v1/refunds`



Retrieve a paginated list of all refunds.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `limit` | `integer` | The number of refunds to return (default: 10, max: 100). |
| `startingAfter` | `string` | A cursor for pagination. |




```php
$refunds = $vatly->refunds->list();

foreach ($refunds as $refund) {
    echo $refund->id . ': ' . $refund->status;
}
```



---

## Refund statuses

| Status | Description |
|--------|-------------|
| `pending` | Refund is being processed |
| `completed` | Refund completed successfully |
| `failed` | Refund failed |
| `canceled` | Refund was canceled |

---

## Helper methods



The Refund object provides convenient helper methods.




```php
$refund->isPending();     // true if status is 'pending'
$refund->isCompleted();   // true if status is 'completed'
$refund->isFailed();      // true if status is 'failed'
$refund->isCanceled();    // true if status is 'canceled'
```
