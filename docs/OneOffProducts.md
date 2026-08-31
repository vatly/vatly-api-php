# One-Off Products

One-off products are single-purchase items (not recurring subscriptions). Create them in the Vatly dashboard or through the API, then use them in checkouts. Live products are reviewed and approved by Vatly before they can be added to checkouts.

## The One-Off Product Resource

Below you'll find all properties for the Vatly One-Off Product resource.

### Properties

| Name | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique identifier for the product (`one_off_product_...`). |
| `name` | `string` | Display name of the product. |
| `description` | `string \| null` | Description of the product. |
| `basePrice` | `Money` | The price as a `Money` object — read `->value` (decimal string) and `->currency` (ISO 4217 code). |
| `taxBehavior` | `string` | Whether `basePrice` is tax-`exclusive` (B2B; tax added at checkout) or tax-`inclusive` (B2C; price already includes tax). Immutable after creation. See `Vatly\API\Types\TaxBehavior`. |
| `productType` | `string` | Tax classification: `saas` or `ebook`. |
| `testmode` | `bool` | Whether this is a test product. |
| `status` | `string` | The status: `active` (purchasable), `pending` (awaiting approval), or `rejected`. |
| `archivedAt` | `string \| null` | When the product was archived (ISO 8601), or `null` while it is on sale. Use `$product->isArchived()` for a boolean. |
| `pendingUpdates` | `object \| null` | The changes that will take effect once a submitted update is approved, or `null` when there is none. Only the fields that differ from the live product are present. |
| `updateStatus` | `string \| null` | Lifecycle of a pending update: `pending`, `reviewing`, or `null`. See `Vatly\API\Types\UpdateStatus`. |
| `createdAt` | `string` | Creation timestamp (ISO 8601). |

---

## Create a product

`POST /v1/one-off-products`



Create a one-off product. A product created with a `live_` token starts in
`pending` status and must be approved by Vatly before it can be added to
checkouts; a product created with a `test_` token is auto-approved (`active`) so
you can trial checkout immediately.

### Required attributes

| Name | Type | Description |
| --- | --- | --- |
| `name` | `string` | Display name (3–255 characters). |
| `description` | `string` | Detailed description of the product. |
| `basePrice` | `array` | Price as `['value' => '299.00', 'currency' => 'EUR']`. |
| `productType` | `string` | Tax classification: `saas` or `ebook`. |

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `taxBehavior` | `string` | `exclusive` (default) or `inclusive`. Immutable after creation. |




```php
$product = $vatly->oneOffProducts->create([
    'name' => 'Premium License',
    'description' => 'Lifetime access to all premium features',
    'basePrice' => ['value' => '299.00', 'currency' => 'EUR'],
    'productType' => 'saas',
    'taxBehavior' => 'exclusive', // optional; defaults to 'exclusive'
]);

echo $product->id;      // one_off_product_...
echo $product->status;  // 'pending' (live) or 'active' (test)
```



---

## Update a product

`PATCH /v1/one-off-products/:id`



Submit an update to a live product. Each request is the **complete set of
changes** relative to the current live product and must contain at least one
field. In live mode the change is held as a pending update and reviewed by Vatly
before it takes effect (`updateStatus` moves `pending` → `reviewing` → applied);
in test mode it is approved automatically. A request that nets to no change
clears any pending update; while an update is being reviewed, further requests
return `409`.

The returned product carries `pendingUpdates` and `updateStatus`.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `name` | `string` | New display name (3–255 characters). |
| `description` | `string` | New description. |
| `basePrice` | `array` | New price as `['value' => '349.00', 'currency' => 'EUR']`. |
| `productType` | `string` | `saas` or `ebook`. |




```php
$product = $vatly->oneOffProducts->update('one_off_product_abc123', [
    'name' => 'Premium License v2',
    'basePrice' => ['value' => '349.00', 'currency' => 'EUR'],
]);

echo $product->updateStatus;                 // 'pending'
echo $product->pendingUpdates->basePrice->value; // '349.00'
```



---

## Archive a product

`POST /v1/one-off-products/:id/archive`



Archive the product so it can no longer be sold. It is hidden from list calls
(unless `includeArchived=true`) and refused by new checkouts; existing orders and
refunds are untouched. Nothing is deleted — the product remains readable by id,
now carrying a non-null `archivedAt`. The call returns no content.




```php
$vatly->oneOffProducts->archive('one_off_product_abc123');

// or, on a resource you already hold:
$product->archive();
```



---

## Unarchive a product

`DELETE /v1/one-off-products/:id/archive`



Put an archived product back on sale. It reappears in listings and can be added
to checkouts again. Returns the product, now on sale (`archivedAt` is `null`).




```php
$product = $vatly->oneOffProducts->unarchive('one_off_product_abc123');

echo $product->isArchived() ? 'archived' : 'on sale'; // on sale
```



---

## Retrieve a product

`GET /v1/one-off-products/:id`



Retrieve a one-off product by its ID.




```php
$product = $vatly->oneOffProducts->get('one_off_product_abc123');

echo $product->name;
echo $product->basePrice->value . ' ' . $product->basePrice->currency;
```



---

## List all products

`GET /v1/one-off-products`



Retrieve a paginated list of all one-off products.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `limit` | `integer` | The number of products to return (default: 10, max: 100). |
| `startingAfter` | `string` | A cursor for pagination. |
| `includeArchived` | `boolean` | Include archived products in the list. Archived products are hidden by default; pass `true` to see them (tell them apart by the non-null `archivedAt`). |




```php
$products = $vatly->oneOffProducts->page();

foreach ($products as $product) {
    echo $product->name . ': ' . $product->basePrice->value . ' ' . $product->basePrice->currency;
}

// Include archived products in the listing:
$all = $vatly->oneOffProducts->page(null, null, null, ['includeArchived' => true]);
```
