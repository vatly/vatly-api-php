<?php

declare(strict_types=1);

namespace Vatly\API\Resources;

use Vatly\API\Resources\Links\CheckoutLinks;
use Vatly\API\Types\CheckoutStatus;

class Checkout extends BaseResource
{
    /**
     * @example checkout_ec853f457eee4276b9ecb2c7558fe557
     */
    public string $id;

    /**
     * @example checkout
     */
    public string $resource;

    /**
     * @example merchant_f7f3cbf96f6c444abd76aafaf99ecde9
     */
    public string $merchantId;

    /**
     * @example order_66fc8a40718b46bea50f1a25f456d243
     */
    public ?string $orderId = null;

    public bool $testmode;

    /**
     * @example https://example.com/checkout/success
     */
    public string $redirectUrlSuccess;

    /**
     * @example https://example.com/checkout/failure
     */
    public string $redirectUrlCanceled;

    /**
     * @var array|object|null
     * @example ["order_id" => "123456"]
     */
    public $metadata = null;

    public CheckoutLinks $links;

    /* @see CheckoutStatus */
    public string $status;

    public ?string $createdAt = null;

    /**
     * Is this checkout created and awaiting payment?
     */
    public function isCreated(): bool
    {
        return $this->status === CheckoutStatus::STATUS_CREATED;
    }

    /**
     * Is this checkout paid successfully?
     */
    public function isPaid(): bool
    {
        return $this->status === CheckoutStatus::STATUS_PAID;
    }

    /**
     * Is this checkout canceled by the customer?
     */
    public function isCanceled(): bool
    {
        return $this->status === CheckoutStatus::STATUS_CANCELED;
    }

    /**
     * Did the checkout payment fail?
     */
    public function isFailed(): bool
    {
        return $this->status === CheckoutStatus::STATUS_FAILED;
    }

    /**
     * Is this checkout expired?
     */
    public function isExpired(): bool
    {
        return $this->status === CheckoutStatus::STATUS_EXPIRED;
    }
}
