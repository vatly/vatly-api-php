<?php

declare(strict_types=1);

namespace Vatly\API\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Checkout;
use Vatly\API\Resources\CheckoutCollection;
use Vatly\API\Resources\Links\PaginationLinks;

/**
 * @extends BaseEndpoint<Checkout, CheckoutCollection>
 */
class CheckoutEndpoint extends BaseEndpoint
{
    protected string $resourcePath = "checkouts";

    const RESOURCE_ID_PREFIX = 'checkout_';

    /**
     * @inheritDoc
     */
    protected function getResourceObject(): Checkout
    {
        return new Checkout($this->client);
    }

    /**
     * A product's `trialDays` must be at least 1 and only applies to a
     * subscription plan; omit it or pass `null` for no trial. A value below 1
     * throws before the request is sent, since Vatly answers it with a `422`.
     *
     * `discounts` takes at most one entry, with a `percentage` or a fixed EUR
     * `amount` (on the products' tax basis) and an optional `description`. The
     * resulting order and its lines list it as {@see \Vatly\API\Types\Discount}.
     *
     * @throws ApiException
     */
    public function create(array $payload, array $options = []): Checkout
    {
        foreach ($payload['products'] ?? [] as $index => $product) {
            $trialDays = is_array($product) ? ($product['trialDays'] ?? null) : null;

            if (is_numeric($trialDays) && $trialDays < 1) {
                throw new ApiException(
                    "products[{$index}].trialDays must be at least 1. Omit it or pass null for no trial."
                );
            }
        }

        return $this->rest_create($payload, $options);
    }

    /**
     * @throws ApiException
     */
    public function get(string $id, array $parameters = []): Checkout
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
    ): CheckoutCollection {
        return $this->rest_list($startingAfter, $endingBefore, $limit, $parameters);
    }

    protected function getResourcePageObject(int $count, PaginationLinks $links): CheckoutCollection
    {
        return new CheckoutCollection($this->client, $count, $links);
    }
}
