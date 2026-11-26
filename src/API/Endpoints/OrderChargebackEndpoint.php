<?php

namespace Vatly\API\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Chargeback;
use Vatly\API\Resources\ChargebackCollection;
use Vatly\API\Resources\Links\PaginationLinks;

/**
 * @extends BaseEndpoint<Chargeback, ChargebackCollection>
 */
class OrderChargebackEndpoint extends BaseEndpoint
{
    protected string $resourcePath = "orders_chargebacks";

    protected function getResourceObject(): Chargeback
    {
        return new Chargeback($this->client);
    }

    protected function getResourcePageObject(int $count, PaginationLinks $links): ChargebackCollection
    {
        return new ChargebackCollection($this->client, $count, $links);
    }

    /**
     * @throws ApiException
     */
    public function getForOrderId(string $orderId, string $chargebackId, array $parameters = []): Chargeback
    {
        $this->parentId = $orderId;

        return parent::rest_read($chargebackId, $parameters);
    }

    /**
     * @throws ApiException
     */
    public function pageForOrderId(
        string $orderId,
        ?string $startingAfter = null,
        ?string $endingBefore = null,
        ?int $limit = null,
        array $parameters = []
    ): ChargebackCollection {
        $this->parentId = $orderId;

        return parent::rest_list($startingAfter, $endingBefore, $limit, $parameters);
    }
}
