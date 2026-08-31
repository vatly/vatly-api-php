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
     * Null until a customer is associated with the checkout (for an
     * anonymous checkout this happens when the buyer completes payment).
     *
     * @example customer_78b146a7de7d417e9d68d7e6ef193d18
     */
    public ?string $customerId = null;

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

    /**
     * The language the hosted checkout is presented in, as sent on creation
     * (one of `en`, `de`, `fr`, `nl`, `es`, `it`, `pt`, `pl`). `null` means you
     * did not specify one, so the checkout picks a language from the shopper's
     * browser.
     */
    public ?string $locale = null;

    /* @see CheckoutStatus */
    public string $status;

    public ?string $createdAt = null;

    public ?string $expiresAt = null;

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
