# Subscription Plans

Subscription plans define recurring billing products. Create them in the Vatly dashboard or through the API, then use them in checkouts. Live plans are reviewed and approved by Vatly before they can be added to checkouts.

## The Subscription Plan Resource

Below you'll find all properties for the Vatly Subscription Plan resource.

### Properties

| Name | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique identifier for the plan (`subscription_plan_...`). |
| `name` | `string` | Display name of the plan. |
| `description` | `string \| null` | Description of the plan. |
| `basePrice` | `Money` | Price per interval as a `Money` object — read `->value` (decimal string) and `->currency` (ISO 4217 code). |
| `interval` | `string` | Billing interval: `day`, `week`, `month`, or `year`. |
| `intervalCount` | `integer` | Number of intervals between billings. |
| `testmode` | `bool` | Whether this is a test plan. |
| `status` | `string` | The status: `active` (subscribable), `pending` (awaiting approval), or `rejected`. |
| `createdAt` | `string` | Creation timestamp (ISO 8601). |

---

## Create a plan

`POST /v1/subscription-plans`



Create a subscription plan. A plan created with a `live_` token starts in
`pending` status and must be approved by Vatly before it can be added to
checkouts; a plan created with a `test_` token is auto-approved (`active`).

### Required attributes

| Name | Type | Description |
| --- | --- | --- |
| `name` | `string` | Display name (3–255 characters). |
| `description` | `string` | Detailed description of the plan. |
| `basePrice` | `array` | Price per interval as `['value' => '29.00', 'currency' => 'EUR']`. |
| `productType` | `string` | Tax classification. Only `saas` is billable on a recurring basis. |
| `interval` | `string` | Billing interval unit. `day` is sandbox-only; live plans support `week`, `month`, `year`. |
| `intervalCount` | `integer` | Interval units between billings (≤ 365 days / 52 weeks / 12 months). For `year`, billing is always annual and this is ignored. |




```php
$plan = $vatly->subscriptionPlans->create([
    'name' => 'Pro Monthly',
    'description' => 'Full access to all Pro features, billed monthly',
    'basePrice' => ['value' => '29.00', 'currency' => 'EUR'],
    'productType' => 'saas',
    'interval' => 'month',
    'intervalCount' => 1,
]);

echo $plan->id;      // subscription_plan_...
echo $plan->status;  // 'pending' (live) or 'active' (test)
```



---

## Retrieve a plan

`GET /v1/subscription-plans/:id`



Retrieve a subscription plan by its ID.




```php
$plan = $vatly->subscriptionPlans->get('subscription_plan_abc123');

echo $plan->name;
echo $plan->basePrice->value . ' ' . $plan->basePrice->currency;
echo $plan->interval;
```



---

## List all plans

`GET /v1/subscription-plans`



Retrieve a paginated list of all subscription plans.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `limit` | `integer` | The number of plans to return (default: 10, max: 100). |
| `startingAfter` | `string` | A cursor for pagination. |




```php
$plans = $vatly->subscriptionPlans->list();

foreach ($plans as $plan) {
    echo $plan->name . ': ' . ($plan->amount / 100) . ' ' . $plan->currency;
}
```
