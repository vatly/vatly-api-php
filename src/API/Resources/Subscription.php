<?php

namespace Vatly\API\Resources;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Links\SubscriptionLinks;
use Vatly\API\Types\Address;
use Vatly\API\Types\Link;
use Vatly\API\Types\Mandate;
use Vatly\API\Types\Money;
use Vatly\API\Types\SubscriptionStatus;

class Subscription extends BaseResource
{
    /**
     * @example subscription_78b146a7de7d417e9d68d7e6ef193d18
     */
    public string $id;

    /**
     * @example subscription
     */
    public string $resource;

    /**
     * @example customer_78b146a7de7d417e9d68d7e6ef193d18
     */
    public string $customerId;

    /**
     * @example subscription_plan_Wt5mNvBxKw7YcZaEjLhR
     */
    public string $subscriptionPlanId;

    public bool $testmode;

    public string $name;

    public string $description;

    public Address $billingAddress;

    /**
     * Price before any taxes and/or discounts are applied.
     */
    public Money $basePrice;

    public int $quantity;

    public string $interval;

    public int $intervalCount;


    /** @see SubscriptionStatus */
    public string $status;

    public string $startedAt;

    public ?string $endedAt;

    public ?string $canceledAt;

    public ?string $renewedAt;

    public ?string $renewedUntil;

    public ?string $nextRenewalAt;

    public ?string $trialUntil = null;

    /**
     * Payment method on file for this subscription, as an inline summary.
     * Null when the subscription has no mandate yet (e.g. ended / canceled-before-payment).
     */
    public ?Mandate $mandate = null;

    /**
     * The target values for a change scheduled to take effect at the next billing
     * cycle — set by an update with `applyImmediately: false` — or `null` when
     * nothing is pending. Always present on both the REST resource and webhook
     * deliveries, so this is the authoritative way to reconcile a pending change.
     * Cleared when the change is applied at renewal or discarded on cancellation.
     *
     * @var \stdClass|null
     */
    public $scheduledUpdate = null;

    public SubscriptionLinks $links;

    /**
     * @return Subscription|BaseResource
     * @throws ApiException
     */
    public function update(array $data = []): BaseResource
    {
        return $this->apiClient->subscriptions->update($this->id, $data);
    }

    /**
     * @param array $data Pre-fill data (`redirectUrlSuccess`, `redirectUrlCanceled`, optional `billingAddress`).
     * @return Link Redirect the customer to this URL to let them update their billing details.
     * @throws ApiException
     */
    public function updateBilling(array $data = []): Link
    {
        return $this->apiClient->subscriptions->updateBilling($this->id, $data);
    }

    /**
     * @throws ApiException
     */
    public function cancel(array $data = []): ?BaseResource
    {
        return $this->apiClient->subscriptions->cancel($this->id, $data);
    }

    /**
     * @throws ApiException
     */
    public function resume(array $data = []): ?BaseResource
    {
        return $this->apiClient->subscriptions->resume($this->id, $data);
    }

    public function isCanceled(): bool
    {
        return $this->status === SubscriptionStatus::CANCELED;
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE;
    }

    public function isOnGracePeriod(): bool
    {
        return $this->status === SubscriptionStatus::ON_GRACE_PERIOD;
    }

    public function isTrial(): bool
    {
        return $this->status === SubscriptionStatus::TRIAL;
    }
}
