# One-Off Products

One-off products are single-purchase items (not recurring subscriptions). Create and manage them in the Vatly dashboard, then use the API to retrieve them.

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
| `status` | `string` | The status: `approved`, `draft`, or `archived`. |
| `createdAt` | `string` | Creation timestamp (ISO 8601). |

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
