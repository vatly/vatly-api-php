<?php

namespace Vatly\API\Endpoints;

use InvalidArgumentException;
use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Customer;
use Vatly\API\Resources\Links\PaginationLinks;
use Vatly\API\Resources\Subscription;
use Vatly\API\Resources\SubscriptionCollection;
use Vatly\API\Types\Link;

/**
 * @extends BaseEndpoint<Subscription, SubscriptionCollection>
 */
class SubscriptionEndpoint extends BaseEndpoint
{
    protected string $resourcePath = "subscriptions";

    const RESOURCE_ID_PREFIX = 'subscription_';

    protected function getResourceObject(): Subscription
    {
        return new Subscription($this->client);
    }

    /**
     * @throws ApiException
     */
    public function get(string $subscriptionId, array $parameters = []): Subscription
    {
        $this->validateSubscriptionId($subscriptionId);

        return $this->rest_read($subscriptionId, $parameters);
    }

    /**
     * @throws ApiException
     */
    public function listForCustomerId(string $customerId): SubscriptionCollection
    {
        return $this->client->customerSubscriptions->pageForCustomerId($customerId);
    }

    /**
     * @throws ApiException
     */
    public function listForCustomer(Customer $customer): SubscriptionCollection
    {
        return $this->listForCustomerId($customer->id);
    }

    /**
     * @throws ApiException
     */
    public function page(
        ?string $startingAfter = null,
        ?string $endingBefore = null,
        ?int $limit = null,
        array $parameters = []
    ): SubscriptionCollection {
        return $this->rest_list($startingAfter, $endingBefore, $limit, $parameters);
    }

    protected function getResourcePageObject(int $count, PaginationLinks $links): SubscriptionCollection
    {
        return new SubscriptionCollection($this->client, $count, $links);
    }

    /**
     * @throws ApiException
     */
    public function update(string $subscriptionId, array $data = [], array $options = []): Subscription
    {
        $this->validateSubscriptionId($subscriptionId);

        return $this->rest_update($subscriptionId, $data, $options);
    }

    /**
     * Create a signed URL where the customer can update the billing details for this subscription
     * (billing address, VAT number, company name).
     *
     * Use the $data parameter to prefill the hosted form with a billing address.
     *
     * @param string $subscriptionId The subscription's ID, for example: subscription_66fc8a40718b46bea50f1a25f456d243
     * @param array $data Pre-fill data (`redirectUrlSuccess`, `redirectUrlCanceled`, optional `billingAddress`).
     *
     * @return Link Redirect the customer to this URL to let them update their billing details.
     * @throws ApiException
     */
    public function updateBilling(string $subscriptionId, array $data = []): Link
    {
        $this->validateSubscriptionId($subscriptionId);

        // POST /subscriptions/{id}/billing-update-link
        $resource = "{$this->getResourcePath()}/" . rawurlencode($subscriptionId) . "/billing-update-link";

        $result = $this->client->performHttpCall(
            self::REST_CREATE,
            $resource,
            $this->parseRequestBody($data),
        );

        return new Link($result->href, $result->type);
    }

    /**
     * Cancel a subscription. By default it stays active until the end of the
     * current billing period (and can be resumed until then); pass
     * `immediately: true` to end it now.
     *
     * @throws ApiException
     */
    public function cancel(string $subscriptionId, bool $immediately = false): void
    {
        $this->validateSubscriptionId($subscriptionId);

        $this->rest_delete($subscriptionId, $immediately ? ['immediately' => true] : []);
    }

    /**
     * Resume a subscription that is currently on grace period.
     *
     * Reverses a pending cancellation while the subscription is still active
     * (i.e. before `renewedUntil`). Once a subscription has fully ended it
     * cannot be resumed.
     *
     * @throws ApiException
     */
    public function resume(string $subscriptionId): Subscription
    {
        $this->validateSubscriptionId($subscriptionId);

        $result = $this->client->performHttpCall(
            self::REST_CREATE,
            "{$this->getResourcePath()}/" . rawurlencode($subscriptionId) . "/resume",
        );

        return $this->hydrate($result);
    }

    /**
     * @param string $subscriptionId
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateSubscriptionId(string $subscriptionId): void
    {
        if (empty($subscriptionId) || strpos($subscriptionId, self::RESOURCE_ID_PREFIX) !== 0) {
            throw new InvalidArgumentException("Invalid subscription ID: '{$subscriptionId}'. A subscription ID should start with '" . self::RESOURCE_ID_PREFIX . "'.");
        }
    }
}
