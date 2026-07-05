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
| `testmode` | `bool` | Whether this is a test product. |
| `status` | `string` | The status: `active` (purchasable), `pending` (awaiting approval), or `rejected`. |
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




```php
$product = $vatly->oneOffProducts->create([
    'name' => 'Premium License',
    'description' => 'Lifetime access to all premium features',
    'basePrice' => ['value' => '299.00', 'currency' => 'EUR'],
    'productType' => 'saas',
]);

echo $product->id;      // one_off_product_...
echo $product->status;  // 'pending' (live) or 'active' (test)
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




```php
$products = $vatly->oneOffProducts->list();

foreach ($products as $product) {
    echo $product->name . ': ' . $product->basePrice->value . ' ' . $product->basePrice->currency;
}
```
