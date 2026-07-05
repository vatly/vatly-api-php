<?php

namespace Vatly\API\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\BaseResource;
use Vatly\API\Resources\BaseResourcePage;
use Vatly\API\Resources\Links\PaginationLinks;
use Vatly\API\Resources\OneOffProduct;
use Vatly\API\Resources\OneOffProductCollection;

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
