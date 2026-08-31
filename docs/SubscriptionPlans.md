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
| `taxBehavior` | `string` | Whether `basePrice` is tax-`exclusive` (B2B) or tax-`inclusive` (B2C). Immutable after creation. See `Vatly\API\Types\TaxBehavior`. |
| `interval` | `string` | Billing interval: `day`, `week`, `month`, or `year`. |
| `intervalCount` | `integer` | Number of intervals between billings. |
| `productType` | `string` | Tax classification. Always `saas` for plans. |
| `testmode` | `bool` | Whether this is a test plan. |
| `status` | `string` | The status: `active` (subscribable), `pending` (awaiting approval), or `rejected`. |
| `archivedAt` | `string \| null` | When the plan was archived (ISO 8601), or `null` while it is open to new business. Use `$plan->isArchived()` for a boolean. |
| `pendingUpdates` | `object \| null` | The changes that will take effect once a submitted update is approved, or `null` when there is none. Only the fields that differ from the live plan are present. |
| `updateStatus` | `string \| null` | Lifecycle of a pending update: `pending`, `reviewing`, or `null`. See `Vatly\API\Types\UpdateStatus`. |
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

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `taxBehavior` | `string` | `exclusive` (default) or `inclusive`. Immutable after creation. |




```php
$plan = $vatly->subscriptionPlans->create([
    'name' => 'Pro Monthly',
    'description' => 'Full access to all Pro features, billed monthly',
    'basePrice' => ['value' => '29.00', 'currency' => 'EUR'],
    'productType' => 'saas',
    'interval' => 'month',
    'intervalCount' => 1,
    'taxBehavior' => 'exclusive', // optional; defaults to 'exclusive'
]);

echo $plan->id;      // subscription_plan_...
echo $plan->status;  // 'pending' (live) or 'active' (test)
```



---

## Update a plan

`PATCH /v1/subscription-plans/:id`



Submit an update to a live plan. Each request is the **complete set of changes**
relative to the current live plan and must contain at least one field. In live
mode the change is held as a pending update and reviewed by Vatly before it takes
effect (`updateStatus` moves `pending` → `reviewing` → applied); in test mode it
is approved automatically. A request that nets to no change clears any pending
update; while an update is being reviewed, further requests return `409`.

The `interval` cannot be changed once the plan has ever been used by a
subscription (active or not) — that returns `409`. The price stays changeable.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `name` | `string` | New display name (3–255 characters). |
| `description` | `string` | New description. |
| `basePrice` | `array` | New price per interval as `['value' => '9.00', 'currency' => 'EUR']`. |
| `interval` | `string` | New billing interval. Requires `intervalCount`. |
| `intervalCount` | `integer` | Required when `interval` is provided. |
| `productType` | `string` | `saas`. |




```php
$plan = $vatly->subscriptionPlans->update('subscription_plan_abc123', [
    'name' => 'Pro Weekly',
    'basePrice' => ['value' => '9.00', 'currency' => 'EUR'],
    'interval' => 'week',
    'intervalCount' => 1,
]);

echo $plan->updateStatus;              // 'pending'
echo $plan->pendingUpdates->interval;  // 'week'
```



---

## Archive a plan

`POST /v1/subscription-plans/:id/archive`



Close a plan to new business. It is hidden from list calls (unless
`includeArchived=true`), refused by new checkouts, and can no longer be switched
to from a subscription update. Subscribers already on the plan are deliberately
untouched — a subscription snapshots its plan at signup and renews off that
snapshot, so archiving never cancels anyone or changes what they are billed.
Nothing is deleted; the plan keeps a non-null `archivedAt`. The call returns no
content.




```php
$vatly->subscriptionPlans->archive('subscription_plan_abc123');

// or, on a resource you already hold:
$plan->archive();
```



---

## Unarchive a plan

`DELETE /v1/subscription-plans/:id/archive`



Re-open an archived plan to new business. It reappears in listings and can be
added to checkouts and switched to again. Returns the plan, now open to new
business (`archivedAt` is `null`).




```php
$plan = $vatly->subscriptionPlans->unarchive('subscription_plan_abc123');
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
| `includeArchived` | `boolean` | Include archived plans in the list. Archived plans are hidden by default; pass `true` to see them (tell them apart by the non-null `archivedAt`). |




```php
$plans = $vatly->subscriptionPlans->page();

foreach ($plans as $plan) {
    echo $plan->name . ': ' . $plan->basePrice->value . ' ' . $plan->basePrice->currency;
}

// Include archived plans in the listing:
$all = $vatly->subscriptionPlans->page(null, null, null, ['includeArchived' => true]);
```
