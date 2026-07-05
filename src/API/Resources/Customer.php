<?php

namespace Vatly\API\Resources;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Links\CustomerLinks;

class Customer extends BaseResource
{
    /**
     * @example customer_78b146a7de7d417e9d68d7e6ef193d18
     */
    public string $id;

    /**
     * @example customer
     */
    public string $resource;

    public ?string $email = null;

    /**
     * Customer's display / account-holder name. This is an identity field for
     * communication (dunning emails, dashboard) — distinct from, and with no
     * effect on, the billing name on invoices.
     */
    public ?string $name = null;

    public ?string $createdAt = null;

    public bool $testmode;

    /**
     * @var array|object|null
     * @example ["customer_id" => "123456"]
     */
    public $metadata;

    public CustomerLinks $links;

    /**
     * Update this customer's identity fields (`name`, `email`). Billing-address
     * details are not editable here.
     *
     * @return Customer|BaseResource
     * @throws ApiException
     */
    public function update(array $data = []): ?BaseResource
    {
        return $this->apiClient->customers->update($this->id, $data);
    }

    /**
     * @throws ApiException
     */
    public function subscriptions()
    {
        return $this->apiClient->customerSubscriptions->pageForCustomerId($this->id);
    }

    /**
     * @throws ApiException
     */
    public function subscription(string $subscriptionId, array $parameters = [])
    {
        return $this->apiClient->customerSubscriptions->getForCustomerId($this->id, $subscriptionId, $parameters);
    }
}
