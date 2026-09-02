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

## Update a customer

`PATCH /v1/customers/:id`



Update a customer's identity fields. Only `name` and `email` may be changed, and
both are optional — send whichever you want to update. Billing-address details
(company name, tax ID, street, city, country, etc.) are **not** editable here;
amend those through the hosted billing-update flow.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `name` | `string \| null` | The customer's name. Pass `null` to clear it. |
| `email` | `string` | The customer's email address. |




```php
$customer = $vatly->customers->update('customer_abc123', [
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
]);

echo $customer->name; // Jane Doe
```

If you already have a `Customer` resource instance:

```php
$customer->update([
    'name' => 'Jane Doe',
]);
```

The SDK generates an idempotency key automatically for the `PATCH` request, or
you can set your own via `$vatly->setIdempotencyKey(...)` beforehand.



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

---

## Recover a customer by email

`GET /v1/customers?email=...`



Look up customers by email address — the way back to a customer id you no longer
have (after a re-sync or reset). The address is canonicalized before matching,
exactly as on write. An address can be held by more than one customer, so this
always returns a (possibly empty) collection.




```php
$customers = $vatly->customers->listByEmail('john@example.com');

if (count($customers) > 0) {
    echo $customers[0]->id; // customer_abc123
}
```

You can also recover in a single call by creating the customer again: create is
get-or-create on the email, so posting a known address returns the existing
customer (`200`) rather than a validation error, while a genuinely new address is
created (`201`). Either way you get the customer back:

```php
$customer = $vatly->customers->create(['email' => 'john@example.com']);

echo $customer->id; // the existing customer id, if the email was already known
```

---

## Create a customer portal session

`POST /v1/customers/:id/portal-sessions`



Create a short-lived, single-use link that sends an authenticated customer
straight to your storefront in Vatly's hosted portal (where they can manage
their subscriptions, billing details, and invoices). The session is locked to
the customer, storefront, merchant, and live/test mode of the API token: the
customer lands directly in that one storefront's portal — never a storefront
picker or any other store. If your account has multiple storefronts, each API
token mints portal links for its own store.

Redirect the customer's browser to the returned `url`. It expires after roughly
15 minutes and can be consumed once, so create a fresh one per visit. The link
is credential-bearing — do not cache or log it.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `returnUrl` | `string` | Absolute HTTPS URL (max 2048 bytes) rendered as a return link in the portal. Vatly never fetches or auto-redirects to it. |

The returned `PortalSession` has three properties:

| Name | Type | Description |
| --- | --- | --- |
| `url` | `string` | Single-use HTTPS URL to redirect the customer to. |
| `expiresAt` | `string` | Expiry of the one-time entry link (ISO 8601). |
| `returnUrl` | `string \| null` | The validated return URL you supplied, or `null`. |




```php
$session = $vatly->customers->createPortalSession('customer_abc123', [
    'returnUrl' => 'https://yourapp.com/account/billing',
]);

// Redirect the customer's browser to the hosted portal.
header('Location: ' . $session->url);
```

The body is optional — omit it to create a session without a return link:

```php
$session = $vatly->customers->createPortalSession('customer_abc123');
```
