# Subscription Plans

Subscription plans define recurring billing products. Create and manage plans in the Vatly dashboard, then use the API to retrieve them.

## The Subscription Plan Resource

Below you'll find all properties for the Vatly Subscription Plan resource.

### Properties

| Name | Type | Description |
| --- | --- | --- |
| `id` | `string` | Unique identifier for the plan (`subscription_plan_...`). |
| `name` | `string` | Display name of the plan. |
| `description` | `string \| null` | Description of the plan. |
| `amount` | `integer` | Price in cents. |
| `currency` | `string` | Three-letter ISO currency code. |
| `interval` | `string` | Billing interval: `day`, `week`, `month`, or `year`. |
| `intervalCount` | `integer` | Number of intervals between billings. |
| `trialDays` | `integer \| null` | Default trial period in days. |
| `testmode` | `bool` | Whether this is a test plan. |
| `status` | `string` | The status: `approved`, `draft`, or `archived`. |
| `createdAt` | `string` | Creation timestamp (ISO 8601). |

---

## Retrieve a plan

`GET /v1/subscription-plans/:id`



Retrieve a subscription plan by its ID.




```php
$plan = $vatly->subscriptionPlans->get('subscription_plan_abc123');

echo $plan->name;
echo $plan->amount / 100 . ' ' . $plan->currency;
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
