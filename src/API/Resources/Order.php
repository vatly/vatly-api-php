<?php

namespace Vatly\API\Resources;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Links\OrderLinks;
use Vatly\API\Types\Address;
use Vatly\API\Types\Link;
use Vatly\API\Types\Money;
use Vatly\API\Types\OrderStatus;
use Vatly\API\Types\TaxSummaryCollection;

class Order extends BaseResource
{
    /**
     * @example order_66fc8a40718b46bea50f1a25f456d243
     */
    public string $id;

    /**
     * @example order
     */
    public string $resource;

    /**
     * Only present when a customer is associated with the order.
     *
     * @example customer_78b146a7de7d417e9d68d7e6ef193d18
     */
    public ?string $customerId = null;

    /**
     * @example 2023-08-11T10:48:51+02:00
     */
    public string $createdAt;

    public bool $testmode;

    public Money $total;

    public Money $subtotal;

    public TaxSummaryCollection $taxSummary;

    /**
     * Amount of the captured payment returned to the customer so far, before
     * taxes — via refund or chargeback (settled). The order `status` stays
     * `paid` regardless; this is the reversal lens.
     */
    public Money $reversedSubtotal;

    /**
     * Remaining amount that can still be refunded, before taxes
     * (`subtotal − reversed − pending reversals`).
     */
    public Money $refundableSubtotal;

    /**
     * @example creditcard
     */
    public ?string $paymentMethod = null;

    public ?string $invoiceNumber = null;

    /** @see OrderStatus */
    public string $status;

    public $metadata = null;

    public OrderLinks $links;

    public Address $customerDetails;

    /**
     * @var OrderLine[]|array
     */
    public array $lines;


    /**
     * Get the line value objects
     *
     * @return OrderLineCollection
     */
    public function lines(): OrderLineCollection
    {
        return ResourceFactory::createCursorResourceCollection(
            $this->apiClient,
            $this->lines,
            OrderLine::class,
            null,
            OrderLineCollection::class,
        );
    }

    /**
     * Is this order created?
     */
    public function isCreated(): bool
    {
        return $this->status === OrderStatus::STATUS_CREATED;
    }

    /**
     * Is this order paid for?
     */
    public function isPaid(): bool
    {
        return $this->status === OrderStatus::STATUS_PAID;
    }

    /**
     * Is this order canceled?
     */
    public function isCanceled(): bool
    {
        return $this->status === OrderStatus::STATUS_CANCELED;
    }

    /**
     * Is this order expired?
     */
    public function isExpired(): bool
    {
        return $this->status === OrderStatus::STATUS_EXPIRED;
    }

    /**
     * Is this order pending?
     */
    public function isPending(): bool
    {
        return $this->status === OrderStatus::STATUS_PENDING;
    }

    /**
     * Has any of the captured payment been returned to the customer (via refund
     * or chargeback)? The order `status` stays `paid` either way.
     */
    public function isReversed(): bool
    {
        return self::compareMoney($this->reversedSubtotal, new Money($this->reversedSubtotal->currency, '0')) > 0;
    }

    /**
     * Has some — but not all — of the payment been returned?
     */
    public function isPartiallyReversed(): bool
    {
        return $this->isReversed()
            && self::compareMoney($this->reversedSubtotal, $this->subtotal) < 0;
    }

    /**
     * Has the full payment been returned? Uses `>=` so rounding/overshoot is
     * treated as fully reversed.
     *
     * Note: this is the *settled* truth and is distinct from "nothing left to
     * refund" — during an in-flight reversal `refundableSubtotal` can be zero
     * while `isFullyReversed()` is still false. Read `refundableSubtotal` for
     * remaining capacity.
     */
    public function isFullyReversed(): bool
    {
        return self::compareMoney($this->reversedSubtotal, $this->subtotal) >= 0;
    }

    /**
     * Compare two money values of the same currency: -1, 0, or 1.
     *
     * Uses bcmath for exact decimal comparison (no float rounding). Comparing
     * across currencies is a programming error and throws.
     *
     * @throws \InvalidArgumentException When the currencies differ.
     */
    private static function compareMoney(Money $a, Money $b): int
    {
        if ($a->currency !== $b->currency) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot compare money values in different currencies: %s and %s.',
                $a->currency,
                $b->currency
            ));
        }

        return bccomp($a->value, $b->value, 12);
    }

    /**
     * @param array $data
     * @return BaseResource|Refund
     * @throws ApiException
     */
    public function refund(array $data)
    {
        return $this->apiClient->orderRefunds->createForOrderId($this->id, $data);
    }

    /**
     * @param array $data
     * @return BaseResource|Refund
     * @throws ApiException
     */
    public function fullRefund(array $data)
    {
        return $this->apiClient->orderRefunds->createFullRefundForOrderId($this->id, $data);
    }

    public function requestAddressUpdateLink(array $data = []): Link
    {
        return $this->apiClient->orders->requestAddressUpdateLink($this->id, $data);
    }
}
