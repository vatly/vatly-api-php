# Refunds

Refunds allow you to return money to your customers for orders.

## The Refund Resource

Below you'll find all properties for the Vatly Refund resource.

### Properties

| Name | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique identifier for the refund (`refund_...`). |
| `resource` | `string` | Always `refund`. |
| `status` | `string` | The refund status (see [Refund statuses](#refund-statuses)). |
| `customerId` | `string` | ID of the customer who receives the refund. |
| `originalOrderId` | `string` | The ID of the order being refunded. |
| `orderId` | `string \| null` | The credit note order ID, `null` until the refund is processed. |
| `total` | `Money` | Total amount including taxes. |
| `subtotal` | `Money` | Subtotal amount before taxes. |
| `taxSummary` | `TaxSummaryCollection` | Tax breakdown by rate. |
| `lines` | `array` | The refund lines. Use `$refund->lines()` for a collection of `RefundLine` objects. |
| `testmode` | `bool` | Whether this is a test refund. |
| `createdAt` | `string` | Creation timestamp (ISO 8601). |
| `links` | `RefundLinks` | Links to this refund, the original order, the credit note order (`null` until there is one) and `customerInvoice`, the customer-portal page of the refund's credit note (`null` until the refund is processed). The `customerInvoice` URL is signed and expires after 72 hours (the customer then sees a page to request a new link). |

---

## Create a partial refund

`POST /v1/orders/:orderId/refunds`



Refund part of an order, line by line.

### Required attributes

| Name | Type | Description |
| --- | --- | --- |
| `items` | `array` | The order lines to refund. See [Refund items](#refund-items) below. |

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `metadata` | `array` | Your custom metadata. |

### Refund items

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `itemId` | `string` | Yes | The ID of the order line to refund. |
| `amount` | `array` | Yes | Amount to refund for this line before taxes: `['value' => '10.00', 'currency' => 'EUR']`. Cannot exceed what is left to refund for the line. |
| `description` | `string \| null` | No | Custom description for this refund line. |
| `descriptionAdditionalLine` | `string \| null` | No | Additional description line. |




```php
$refund = $vatly->orderRefunds->createForOrderId('order_abc123', [
    'items' => [
        [
            'itemId' => 'order_item_abc123',
            'amount' => ['value' => '10.00', 'currency' => 'EUR'],
            'description' => 'Partial refund',
        ],
    ],
]);

echo $refund->id;
echo $refund->status;
```



---

## Create a full refund

`POST /v1/orders/:orderId/refunds/full`



Refund everything that can still be refunded on an order.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `metadata` | `array` | Your custom metadata. |




```php
$refund = $vatly->orderRefunds->createFullRefundForOrderId('order_abc123', []);

// Or from an order you already fetched
$refund = $order->fullRefund([]);
```



---

## Retrieve a refund

`GET /v1/refunds/:id`



Retrieve a refund by its ID.




```php
$refund = $vatly->refunds->get('refund_abc123');

echo $refund->status;
echo $refund->total->value . ' ' . $refund->total->currency;
```



---

## List all refunds

`GET /v1/refunds`



Retrieve a paginated list of all refunds.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `startingAfter` | `string` | A cursor for pagination: return the page after this refund ID. |
| `endingBefore` | `string` | A cursor for pagination: return the page before this refund ID. |
| `limit` | `integer` | The number of refunds to return (default: 10, max: 100). |




```php
$refunds = $vatly->refunds->page();

foreach ($refunds as $refund) {
    echo $refund->id . ': ' . $refund->status;
}

// Pass the cursors and limit in order: startingAfter, endingBefore, limit
$refunds = $vatly->refunds->page('refund_last_id', null, 25);

// One order's refunds (GET /v1/orders/:orderId/refunds)
$refunds = $vatly->orderRefunds->pageForOrderId('order_abc123');
```



---

## Cancel a refund

`DELETE /v1/orders/:orderId/refunds/:refundId`



Cancel a refund that has not been processed yet. Once it is `processing`, cancellation is no longer possible.




```php
$vatly->orderRefunds->cancelRefundForOrderId('order_abc123', 'refund_abc123');

// Or from a refund you already fetched
$refund->cancel();
```



---

## Refund statuses

| Status | Description |
|--------|-------------|
| `pending` | Refund is ready to be sent to the bank |
| `queued` | Refund is queued due to a lack of balance |
| `processing` | Refund is being processed (cancellation is no longer possible) |
| `refunded` | Refund has been processed successfully |
| `failed` | Refund failed after processing |
| `canceled` | Refund was canceled |

The Refund object has no status helper methods. Compare `$refund->status` against the `Vatly\API\Types\RefundStatus` constants:

```php
use Vatly\API\Types\RefundStatus;

if ($refund->status === RefundStatus::REFUNDED) {
    // The money is on its way back to the customer
}
```
