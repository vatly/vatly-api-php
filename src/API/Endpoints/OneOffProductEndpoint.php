<?php

namespace Vatly\API\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\BaseResource;
use Vatly\API\Resources\BaseResourcePage;
use Vatly\API\Resources\Links\PaginationLinks;
use Vatly\API\Resources\OneOffProduct;
use Vatly\API\Resources\OneOffProductCollection;
use Vatly\API\Resources\ResourceFactory;

class OneOffProductEndpoint extends BaseEndpoint
{
    protected string $resourcePath = "one-off-products";

    const RESOURCE_ID_PREFIX = 'one_off_product_';

    protected function getResourceObject(): OneOffProduct
    {
        return new OneOffProduct($this->client);
    }

    /**
     * Create a one-off product. Products created with a `live_` token start in
     * `pending` status and must be approved by Vatly before use in checkouts;
     * products created with a `test_` token are auto-approved (`active`).
     *
     * @return OneOffProduct|BaseResource
     * @throws ApiException
     */
    public function create(array $payload, array $filters = []): BaseResource
    {
        return $this->rest_create($payload, $filters);
    }

    /**
     * @throws ApiException
     * @return OneOffProduct|BaseResource
     */
    public function get(string $id, array $parameters = []): BaseResource
    {
        return $this->rest_read($id, $parameters);
    }

    /**
     * Submit an update to a live one-off product (PATCH). Each request is the
     * complete set of changes relative to the current live product. In live mode
     * the change is held as a pending update and reviewed by Vatly before it takes
     * effect (`updateStatus` moves `pending` → `reviewing` → applied); in test mode
     * it is approved automatically. The returned product carries `pendingUpdates`
     * and `updateStatus`.
     *
     * @return OneOffProduct|BaseResource|null
     * @throws ApiException
     */
    public function update(string $id, array $data = [], array $filters = []): ?BaseResource
    {
        return $this->rest_update($id, $data, $filters);
    }

    /**
     * Archive a one-off product, taking it out of the sellable catalogue
     * (`POST /v1/one-off-products/{id}/archive`). Archived products are hidden
     * from listings unless `includeArchived=true` is passed, and refused by new
     * checkouts. The API returns `204 No Content`, so there is nothing to hydrate.
     *
     * @throws ApiException
     */
    public function archive(string $id, array $filters = []): void
    {
        if (empty($id)) {
            throw new ApiException("Invalid resource id.");
        }

        $this->client->performHttpCall(
            self::REST_CREATE,
            "{$this->getResourcePath()}/" . urlencode($id) . "/archive" . $this->buildQueryString($filters),
        );
    }

    /**
     * Put an archived product back on sale
     * (`DELETE /v1/one-off-products/{id}/archive`). Returns the product, now on
     * sale again.
     *
     * @return OneOffProduct|BaseResource|null
     * @throws ApiException
     */
    public function unarchive(string $id, array $filters = []): ?BaseResource
    {
        if (empty($id)) {
            throw new ApiException("Invalid resource id.");
        }

        $result = $this->client->performHttpCall(
            self::REST_DELETE,
            "{$this->getResourcePath()}/" . urlencode($id) . "/archive" . $this->buildQueryString($filters),
        );

        if ($result === null) {
            return null;
        }

        return ResourceFactory::createResourceFromApiResult($result, $this->getResourceObject());
    }

    /**
     * @return OneOffProductCollection|BaseResourcePage
     * @throws ApiException
     */
    public function page(
        ?string $startingAfter = null,
        ?string $endingBefore = null,
        ?int $limit = null,
        array $parameters = []
    ): BaseResourcePage {
        return $this->rest_list($startingAfter, $endingBefore, $limit, $parameters);
    }

    protected function getResourcePageObject(int $count, PaginationLinks $links): BaseResourcePage
    {
        return new OneOffProductCollection($this->client, $count, $links);
    }
}
