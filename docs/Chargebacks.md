# Chargebacks

Chargebacks occur when a customer disputes a payment with their bank. Vatly notifies you via webhooks when chargebacks happen.

## The Chargeback Resource

Below you'll find all properties for the Vatly Chargeback resource.

### Properties

| Name | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique identifier for the chargeback (`chargeback_...`). |
| `resource` | `string` | Always `chargeback`. |
| `customerId` | `string` | ID of the customer who disputed the payment. |
| `testmode` | `bool` | Whether this is a test chargeback. |
| `status` | `string` | The dispute status (see [Chargeback statuses](#chargeback-statuses)). |
| `amount` | `Money` | Amount of the chargeback. |
| `settlementAmount` | `Money` | Amount deducted from settlement (may differ from `amount` due to currency conversion or fees). |
| `total` | `Money` | Total amount including taxes. |
| `subtotal` | `Money` | Subtotal amount before taxes. |
| `taxSummary` | `TaxSummaryCollection` | Tax breakdown by rate. |
| `reason` | `string` | Reason code for the dispute (e.g. `fraud`, `product_not_received`, `duplicate`). |
| `originalOrderId` | `string` | The ID of the original order that was disputed. |
| `orderId` | `string \| null` | The credit note order ID, `null` until the chargeback is processed. |
| `createdAt` | `string` | Creation timestamp (ISO 8601). |
| `links` | `ChargebackLinks` | Links to this chargeback, the original order, the credit note order (`null` until there is one) and `customerInvoice`, the customer-portal page of the chargeback's credit note (`null` until the chargeback is lost). The `customerInvoice` URL is signed and expires after 72 hours (the customer then sees a page to request a new link). |

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
| `startingAfter` | `string` | A cursor for pagination: return the page after this chargeback ID. |
| `endingBefore` | `string` | A cursor for pagination: return the page before this chargeback ID. |
| `limit` | `integer` | The number of chargebacks to return (default: 10, max: 100). |




```php
$chargebacks = $vatly->chargebacks->page();

foreach ($chargebacks as $chargeback) {
    echo $chargeback->id . ': ' . $chargeback->reason;
}

// Pass the cursors and limit in order: startingAfter, endingBefore, limit
$chargebacks = $vatly->chargebacks->page('chargeback_last_id', null, 25);
```

---

## Chargeback statuses

| Status | Description |
|--------|-------------|
| `pending` | Dispute received, awaiting action |
| `requires_amount_review` | The disputed amount exceeds the original order, so completion is held pending review |
| `accepted` | The dispute was accepted |
| `rejected` | The dispute was rejected |
| `evidence_submitted` | Evidence submitted, awaiting a decision |
| `won` | Dispute resolved in the merchant's favor |
| `lost` | Dispute resolved in the customer's favor |
