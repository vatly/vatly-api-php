<?php

namespace Vatly\API\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Links\PaginationLinks;
use Vatly\API\Resources\Order;
use Vatly\API\Resources\OrderCollection;
use Vatly\API\Types\Link;

/**
 * @extends BaseEndpoint<Order, OrderCollection>
 */
class OrderEndpoint extends BaseEndpoint
{
    protected string $resourcePath = "orders";

    const RESOURCE_ID_PREFIX = 'order_';

    protected function getResourceObject(): Order
    {
        return new Order($this->client);
    }


    /**
     * @throws ApiException
     */
    public function get(string $id, array $parameters = []): Order
    {
        $this->validateOrderId($id);

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
    ): OrderCollection {
        return $this->rest_list($startingAfter, $endingBefore, $limit, $parameters);
    }

    /**
     * Request a signed URL where the customer can update their billing address for this order
     * (VAT number, company name, address). Valid for a limited time (typically 24 hours).
     *
     * @throws ApiException
     */
    public function requestAddressUpdateLink(string $id): Link
    {
        $this->validateOrderId($id);

        $result = $this->client->performHttpCall(
            self::REST_CREATE,
            "{$this->getResourcePath()}/" . rawurlencode($id) . "/invoice-update-link",
        );

        return new Link($result->href, $result->type);
    }

    protected function getResourcePageObject(int $count, PaginationLinks $links): OrderCollection
    {
        return new OrderCollection($this->client, $count, $links);
    }

    private function validateOrderId(string $orderId): void
    {
        if (empty($orderId) || strpos($orderId, self::RESOURCE_ID_PREFIX) !== 0) {
            throw new \InvalidArgumentException("Invalid order ID: '{$orderId}'. An order ID should start with '" . self::RESOURCE_ID_PREFIX . "'.");
        }
    }
}
