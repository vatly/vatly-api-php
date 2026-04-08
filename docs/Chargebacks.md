# Chargebacks

Chargebacks occur when a customer disputes a payment with their bank. Vatly notifies you via webhooks when chargebacks happen.

## The Chargeback Resource

Below you'll find all properties for the Vatly Chargeback resource.

### Properties

| Name | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique identifier for the chargeback (`chargeback_...`). |
| `resource` | `string` | Always `chargeback`. |
| `merchantId` | `string` | The merchant ID. |
| `testmode` | `bool` | Whether this is a test chargeback. |
| `amount` | `object` | Chargeback amount (`value` and `currency`). |
| `settlementAmount` | `object` | Amount deducted from settlement (may differ from `amount`). |
| `reason` | `string` | Reason code for the dispute (e.g. `fraud`, `product_not_received`, `duplicate`). |
| `originalOrderId` | `string` | The ID of the original order that was disputed. |
| `orderId` | `string\|null` | The credit note order ID (after processing). |
| `createdAt` | `string` | Creation timestamp (ISO 8601). |

---

## Retrieve a chargeback

`GET /v1/chargebacks/:id`



Retrieve a chargeback by its ID.




```php
$chargeback = $vatly->chargebacks->get('chargeback_abc123');

echo $chargeback->reason;
echo $chargeback->amount->value . ' ' . $chargeback->amount->currency;
echo $chargeback->originalOrderId;
```



---

## List all chargebacks

`GET /v1/chargebacks`



Retrieve a paginated list of all chargebacks.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `limit` | `integer` | The number of chargebacks to return (default: 10, max: 100). |
| `startingAfter` | `string` | A cursor for pagination. |




```php
$chargebacks = $vatly->chargebacks->list();

foreach ($chargebacks as $chargeback) {
    echo $chargeback->id . ': ' . $chargeback->reason;
}
```
