<?php

namespace Vatly\API\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Links\PaginationLinks;
use Vatly\API\Resources\Order;
use Vatly\API\Resources\Refund;
use Vatly\API\Resources\RefundCollection;

/**
 * @extends BaseEndpoint<Refund, RefundCollection>
 */
class OrderRefundEndpoint extends BaseEndpoint
{
    protected string $resourcePath = "orders_refunds";

    protected function getResourceObject(): Refund
    {
        return new Refund($this->client);
    }

    protected function getResourcePageObject(int $count, PaginationLinks $links): RefundCollection
    {
        return new RefundCollection($this->client, $count, $links);
    }

    /**
     * @throws ApiException
     */
    public function getForOrderId(string $orderId, string $refundId, array $parameters = []): Refund
    {
        $this->parentId = $orderId;

        return parent::rest_read($refundId, $parameters);
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
    ): RefundCollection {
        $this->parentId = $orderId;

        return parent::rest_list($startingAfter, $endingBefore, $limit, $parameters);
    }

    /**
     * Refund part of an order.
     *
     * @param array<string, mixed> $data The refund request (`items`, optional `metadata`).
     * @param array{idempotencyKey?: string} $options
     * @throws ApiException
     */
    public function createForOrder(Order $order, array $data, array $options = []): Refund
    {
        return $this->createForOrderId($order->id, $data, $options);
    }

    /**
     * Refund part of an order.
     *
     * @param string $orderId The order's ID, for example: order_66fc8a40718b46bea50f1a25f456d243
     * @param array<string, mixed> $data The refund request (`items`, optional `metadata`).
     * @param array{idempotencyKey?: string} $options
     * @throws ApiException
     */
    public function createForOrderId(string $orderId, array $data, array $options = []): Refund
    {
        $this->parentId = $orderId;

        return parent::rest_create($data, $options);
    }

    /**
     * Refund the rest of an order.
     *
     * @param string $orderId The order's ID, for example: order_66fc8a40718b46bea50f1a25f456d243
     * @param array<string, mixed> $data Optional `metadata`.
     * @param array{idempotencyKey?: string} $options
     * @throws ApiException
     */
    public function createFullRefundForOrderId(string $orderId, array $data = [], array $options = []): Refund
    {
        $this->parentId = $orderId;

        // A clone, because this instance is shared by every later call on the client.
        $fullRefunds = clone $this;
        $fullRefunds->setResourcePath($this->resourcePath . '/full');

        return $fullRefunds->rest_create($data, $options);
    }

    /**
     * Cancel a refund that has not been processed yet.
     *
     * @throws ApiException when the refund cannot be canceled
     */
    public function cancelRefundForOrderId(string $orderId, string $refundId): void
    {
        $this->parentId = $orderId;

        parent::rest_delete($refundId);
    }
}
