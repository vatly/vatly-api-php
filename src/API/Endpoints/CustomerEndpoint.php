<?php

declare(strict_types=1);

namespace Vatly\API\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\BaseResource;
use Vatly\API\Resources\BaseResourcePage;
use Vatly\API\Resources\Customer;
use Vatly\API\Resources\CustomerCollection;
use Vatly\API\Resources\Links\PaginationLinks;
use Vatly\API\Types\PortalSession;

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

    /**
     * Create a short-lived, single-use hosted customer portal session
     * (`POST /v1/customers/{customerId}/portal-sessions`).
     *
     * Redirect the customer's browser to the returned `url`. The link is locked
     * to this customer, storefront, merchant, and mode, expires after roughly 15
     * minutes, and can be consumed once. It is credential-bearing — do not cache
     * or log it. The request body is optional; pass `returnUrl` (an absolute
     * HTTPS URL) to render a return link in the portal.
     *
     * @param string $id The customer's ID, e.g. customer_7kBmRtPvXw2NjLhYcZaE
     * @param array $data Optional body (`returnUrl`).
     *
     * @return PortalSession
     * @throws ApiException
     */
    public function createPortalSession(string $id, array $data = []): PortalSession
    {
        if (empty($id)) {
            throw new ApiException("Invalid resource id.");
        }

        $resource = "{$this->getResourcePath()}/" . urlencode($id) . "/portal-sessions";

        $result = $this->client->performHttpCall(
            self::REST_CREATE,
            $resource,
            $this->parseRequestBody($data),
        );

        return PortalSession::createResourceFromApiResult($result);
    }

    protected function getResourcePageObject(int $count, PaginationLinks $links): BaseResourcePage
    {
        return new CustomerCollection($this->client, $count, $links);
    }
}
