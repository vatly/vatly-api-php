<?php

declare(strict_types=1);

namespace Vatly\API\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\BaseResource;
use Vatly\API\Resources\BaseResourcePage;
use Vatly\API\Resources\Customer;
use Vatly\API\Resources\CustomerCollection;
use Vatly\API\Resources\Links\PaginationLinks;

class CustomerEndpoint extends BaseEndpoint
{
    protected string $resourcePath = "customers";

    const RESOURCE_ID_PREFIX = 'customer_';

    /**
     * @inheritDoc
     */
    protected function getResourceObject(): Customer
    {
        return new Customer($this->client);
    }

    /**
     * @return Customer|BaseResource
     *@throws ApiException
     */
    public function create(array $payload, array $filters = []): BaseResource
    {
        return $this->rest_create($payload, $filters);
    }

    /**
     * @return Customer|BaseResource
     *@throws ApiException
     */
    public function get(string $id, array $parameters = []): BaseResource
    {
        return $this->rest_read($id, $parameters);
    }

    /**
     * Update a customer's identity fields. Only `name` and `email` are editable;
     * both are optional. Billing-address fields are not accepted here.
     *
     * @return Customer|BaseResource|null
     * @throws ApiException
     */
    public function update(string $id, array $data = [], array $filters = []): ?BaseResource
    {
        return $this->rest_update($id, $data, $filters);
    }

    /**
     * @return CustomerCollection|BaseResourcePage
     * @throws ApiException
     */
    public function page(?string $startingAfter = null, ?string $endingBefore = null, ?int $limit = null, array $parameters = [])
    {
        return $this->rest_list($startingAfter, $endingBefore, $limit, $parameters);
    }

    /**
     * Look up customers by email address — the way back to a customer id you no
     * longer have. The address is canonicalized before matching, exactly as on
     * write. An address can be held by more than one customer, in which case all
     * of them are returned, so this always yields a (possibly empty) collection.
     *
     * @return CustomerCollection|BaseResourcePage
     * @throws ApiException
     */
    public function listByEmail(string $email, array $parameters = []): BaseResourcePage
    {
        return $this->rest_list(null, null, null, array_merge($parameters, ['email' => $email]));
    }

    protected function getResourcePageObject(int $count, PaginationLinks $links): BaseResourcePage
    {
        return new CustomerCollection($this->client, $count, $links);
    }
}
