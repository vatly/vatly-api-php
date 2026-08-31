<?php

namespace Vatly\API\Resources;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Links\SubscriptionPlanLinks;
use Vatly\API\Types\Money;

class SubscriptionPlan extends BaseResource
{
    /**
     * @example subscription_plan_78b146a7de7d417e9d68d7e6ef193d18
     */
    public string $id;

    /**
     * @example subscription_plan
     */
    public string $resource;

    public string $name;

    public string $description;

    public string $interval;

    public int $intervalCount;

    /**
     * Price before any taxes and/or discounts are applied.
     */
    public Money $basePrice;

    /**
     * Whether `basePrice` is tax-exclusive or tax-inclusive.
     *
     * @see \Vatly\API\Types\TaxBehavior
     */
    public string $taxBehavior;

    /**
     * What kind of product this plan sells. Only `saas` is available on a plan.
     */
    public ?string $productType = null;

    public bool $testmode;

    /** @see \Vatly\API\Types\ProductStatus */
    public string $status;

    /**
     * When this plan was archived (ISO 8601), or `null` while it is open to new business.
     */
    public ?string $archivedAt = null;

    /**
     * The changes that will take effect once a submitted update is approved, or
     * `null` when there is no pending update. Only the fields that differ from
     * the live plan are present.
     *
     * @var \stdClass|null
     */
    public $pendingUpdates = null;

    /**
     * Lifecycle of a pending update, or `null` when there is none.
     *
     * @see \Vatly\API\Types\UpdateStatus
     */
    public ?string $updateStatus = null;

    public ?string $createdAt = null;

    public SubscriptionPlanLinks $links;

    /**
     * Whether this plan is currently archived (closed to new business).
     */
    public function isArchived(): bool
    {
        return $this->archivedAt !== null;
    }

    /**
     * Submit an update to this plan. In live mode the change is held as a
     * pending update and reviewed by Vatly before it takes effect.
     *
     * @return SubscriptionPlan|BaseResource|null
     * @throws ApiException
     */
    public function update(array $data = [], array $filters = []): ?BaseResource
    {
        return $this->apiClient->subscriptionPlans->update($this->id, $data, $filters);
    }

    /**
     * Archive this plan, closing it to new business. Existing subscribers are untouched.
     *
     * @throws ApiException
     */
    public function archive(array $filters = []): void
    {
        $this->apiClient->subscriptionPlans->archive($this->id, $filters);
    }

    /**
     * Re-open this plan to new business.
     *
     * @return SubscriptionPlan|BaseResource|null
     * @throws ApiException
     */
    public function unarchive(array $filters = []): ?BaseResource
    {
        return $this->apiClient->subscriptionPlans->unarchive($this->id, $filters);
    }
}
