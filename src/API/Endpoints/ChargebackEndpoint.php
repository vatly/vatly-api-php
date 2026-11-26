<?php

namespace Vatly\API\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Chargeback;
use Vatly\API\Resources\ChargebackCollection;
use Vatly\API\Resources\Links\PaginationLinks;

/**
 * @extends BaseEndpoint<Chargeback, ChargebackCollection>
 */
class ChargebackEndpoint extends BaseEndpoint
{
    protected string $resourcePath = "chargebacks";

    const RESOURCE_ID_PREFIX = 'chargeback_';

    protected function getResourceObject(): Chargeback
    {
        return new Chargeback($this->client);
    }


    /**
     * @throws ApiException
     */
    public function get(string $id, array $parameters = []): Chargeback
    {
        return $this->rest_read($id, $parameters);
    }

    /**
     * @throws ApiException
     */
    public function page(
        ?string $startingAfter = null,
        ?string $endingBefore = null,
        ?int $limit = null,
        array $parameters = []
    ): ChargebackCollection {
        return $this->rest_list($startingAfter, $endingBefore, $limit, $parameters);
    }

    protected function getResourcePageObject(int $count, PaginationLinks $links): ChargebackCollection
    {
        return new ChargebackCollection($this->client, $count, $links);
    }
}
