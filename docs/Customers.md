# Customers

Customers represent your end users who purchase products through Vatly.

## The Customer Resource

Below you'll find all properties for the Vatly Customer resource.

### Properties

| Name | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique identifier for the customer (`customer_...`). |
| `email` | `string` | Customer's email address. |
| `name` | `string \| null` | Customer's name. |
| `testmode` | `bool` | Whether this is a test customer. |
| `metadata` | `array` | Your custom metadata. |
| `createdAt` | `string` | Creation timestamp (ISO 8601). |

---

## Create a customer

`POST /v1/customers`



Create a new customer.

### Required attributes

| Name | Type | Description |
| --- | --- | --- |
| `email` | `string` | The customer's email address. |

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `name` | `string` | The customer's name. |
| `metadata` | `array` | Your custom metadata. |




```php
$customer = $vatly->customers->create([
    'email' => 'john@example.com',
    'name' => 'John Doe',
    'metadata' => [
        'user_id' => '12345',
    ],
]);

echo $customer->id;  // customer_abc123
```



---

## Retrieve a customer

`GET /v1/customers/:id`



Retrieve a customer by their ID.




```php
$customer = $vatly->customers->get('customer_abc123');

echo $customer->email;
echo $customer->name;
```



---

## List all customers

`GET /v1/customers`



Retrieve a paginated list of all your customers.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `limit` | `integer` | The number of customers to return (default: 10, max: 100). |
| `startingAfter` | `string` | A cursor for pagination. Returns results after this customer ID. |




```php
$customers = $vatly->customers->list();

foreach ($customers as $customer) {
    echo $customer->email;
}

// Pagination
$customers = $vatly->customers->list([
    'limit' => 25,
    'startingAfter' => 'customer_last_id',
]);
```
