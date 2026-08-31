<?php

namespace Vatly\API\Resources;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Links\OneOffProductLinks;
use Vatly\API\Types\Money;

class OneOffProduct extends BaseResource
{
    /**
     * @example one_off_product_78b146a7de7d417e9d68d7e6ef193d18
     */
    public string $id;

    /**
     * @example one_off_product
     */
    public string $resource;

    public string $name;

    public string $description;

    /**
     * Price before any taxes and/or discounts are applied.
     */
    public Money $basePrice;

    /**
     * Whether `basePrice` is tax-exclusive or tax-inclusive.
     *
     * @see \Vatly\API\Types\TaxBehavior
     */
    public string $taxBehavior;

    /**
     * What kind of product this is: `saas` or `ebook`.
     */
    public ?string $productType = null;

    public bool $testmode;

    /** @see \Vatly\API\Types\ProductStatus */
    public string $status;

    /**
     * When this product was archived (ISO 8601), or `null` while it is on sale.
     */
    public ?string $archivedAt = null;

    /**
     * The changes that will take effect once a submitted update is approved, or
     * `null` when there is no pending update. Only the fields that differ from
     * the live product are present.
     *
     * @var \stdClass|null
     */
    public $pendingUpdates = null;

    /**
     * Lifecycle of a pending update, or `null` when there is none.
     *
     * @see \Vatly\API\Types\UpdateStatus
     */
    public ?string $updateStatus = null;

    public ?string $createdAt = null;

    public OneOffProductLinks $links;

    /**
     * Whether this product is currently archived (taken out of the sellable catalogue).
     */
    public function isArchived(): bool
    {
        return $this->archivedAt !== null;
    }

    /**
     * Submit an update to this product. In live mode the change is held as a
     * pending update and reviewed by Vatly before it takes effect.
     *
     * @return OneOffProduct|BaseResource|null
     * @throws ApiException
     */
    public function update(array $data = [], array $filters = []): ?BaseResource
    {
        return $this->apiClient->oneOffProducts->update($this->id, $data, $filters);
    }

    /**
     * Archive this product, taking it out of the sellable catalogue.
     *
     * @throws ApiException
     */
    public function archive(array $filters = []): void
    {
        $this->apiClient->oneOffProducts->archive($this->id, $filters);
    }

    /**
     * Put this product back on sale.
     *
     * @return OneOffProduct|BaseResource|null
     * @throws ApiException
     */
    public function unarchive(array $filters = []): ?BaseResource
    {
        return $this->apiClient->oneOffProducts->unarchive($this->id, $filters);
    }
}
