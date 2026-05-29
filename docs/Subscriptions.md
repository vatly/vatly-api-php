# Subscriptions

Subscriptions are created automatically when a customer completes a checkout for a subscription plan. You can then manage the subscription lifecycle through the API.

## The Subscription Resource

Below you'll find all properties for the Vatly Subscription resource.

### Properties

| Name | Type                | Description |
| --- |---------------------| --- |
| `id` | `string`            | Unique identifier for the subscription (`subscription_...`). |
| `resource` | `string`            | Resource type, always `subscription`. |
| `customerId` | `string`            | ID of the customer who owns this subscription. |
| `subscriptionPlanId` | `string`            | ID of the subscription plan this subscription is based on. |
| `testmode` | `bool`              | Whether this subscription is in test mode. |
| `name` | `string`            | Name of the subscription (from the plan). |
| `description` | `string`            | Description of the subscription. |
| `billingAddress` | `Address`           | Customer's billing address for this subscription. |
| `basePrice` | `Money`             | Price per billing cycle before taxes. |
| `quantity` | `int`               | Number of subscription units (e.g. seats). |
| `interval` | `string`            | Billing interval unit: `day`, `week`, `month`, or `year`. |
| `intervalCount` | `int`               | Number of interval units between billing cycles. |
| `status` | `string`            | The subscription status (see [Subscription statuses](#subscription-statuses)). |
| `startedAt` | `string`            | When the subscription started (ISO 8601). |
| `endedAt` | `string \| null`    | When the subscription ended (ISO 8601). |
| `canceledAt` | `string \| null`    | When the subscription was canceled (ISO 8601). |
| `renewedAt` | `string \| null`    | When the subscription was last renewed (ISO 8601). |
| `renewedUntil` | `string \| null`    | Current billing period end date (ISO 8601). |
| `nextRenewalAt` | `string \| null`    | When the next renewal will be attempted (ISO 8601). Null if canceled or ended. |
| `trialUntil` | `string \| null`    | When the trial period ends (ISO 8601). Null if not in trial or trial has ended. |
| `mandate` | `Mandate \| null`   | Payment method on file (`method`, `maskedIdentifier`). Null when the subscription has no mandate yet. |
| `links` | `SubscriptionLinks` | HATEOAS links to related resources (`self`, `customer`). |

---

## Retrieve a subscription

`GET /v1/subscriptions/:id`



Retrieve a subscription by its ID.




```php
$subscription = $vatly->subscriptions->get('subscription_abc123');

echo $subscription->status;
echo $subscription->subscriptionPlanId;

if ($subscription->isActive()) {
    echo 'Subscription is active';
}
```



---

## List all subscriptions

`GET /v1/subscriptions`



Retrieve a paginated list of all subscriptions.

### Optional attributes

| Name | Type | Description |
| --- | --- | --- |
| `limit` | `integer` | The number of subscriptions to return (default: 10, max: 100). |
| `startingAfter` | `string` | A cursor for pagination. |
| `customerId` | `string` | Filter by customer ID. |




```php
$subscriptions = $vatly->subscriptions->list();

foreach ($subscriptions as $subscription) {
    echo $subscription->id . ': ' . $subscription->status;
}

// Filter by customer
$subscriptions = $vatly->subscriptions->list([
    'customerId' => 'customer_abc123',
]);
```



---

## Update a subscription

`PATCH /v1/subscriptions/:id`



Update mutable subscription fields such as quantity.

```php
$subscription = $vatly->subscriptions->update('subscription_123', [
    'quantity' => 2,
], [
    'idempotencyKey' => 'subscription-update-123',
]);
```

If you already have a `Subscription` resource instance, set the key on the client before calling the resource method:

```php
$vatly->setIdempotencyKey('subscription-update-123');

$subscription->update([
    'quantity' => 2,
]);
```

If you do not provide a custom key, the SDK generates one automatically for the `PATCH` request.



---

## Cancel a subscription

`DELETE /v1/subscriptions/:id`



Cancel a subscription. The subscription will remain active until the end of the current billing period.




```php
$subscription = $vatly->subscriptions->cancel('subscription_abc123');

// Subscription is now on grace period until current period ends
echo $subscription->status;        // 'on_grace_period'
echo $subscription->renewedUntil;  // Current billing period end
```



---

## Resume a subscription

`POST /v1/subscriptions/:id/resume`



Reverse a pending cancellation while the subscription is still on its grace period. Once a subscription has fully ended it cannot be resumed.




```php
$subscription = $vatly->subscriptions->resume('subscription_abc123');

echo $subscription->status; // 'active'
```

If you already have a `Subscription` resource instance:

```php
$subscription->resume();
```



---

## Subscription statuses

| Status | Description |
|--------|-------------|
| `active` | Subscription is active and will renew |
| `created` | Subscription has been created but not yet started |
| `trial` | Subscription is in trial period |
| `on_grace_period` | Subscription is canceled but still active until the period ends |
| `paused` | Subscription is temporarily paused |
| `canceled` | Subscription has been canceled |

---

## Helper methods



The Subscription object provides convenient helper methods.




```php
$subscription->isActive();         // true if status is 'active'
$subscription->isTrial();          // true if status is 'trial'
$subscription->isOnGracePeriod();  // true if status is 'on_grace_period'
$subscription->isCanceled();       // true if status is 'canceled'
```
