<?php

namespace Vatly\API\Resources;

use Vatly\API\Resources\Links\ChargebackLinks;
use Vatly\API\Types\Money;
use Vatly\API\Types\TaxSummaryCollection;

class Chargeback extends BaseResource
{
    /**
     * @example chargeback_78b146a7de7d417e9d68d7e6ef193d18
     */
    public string $id;

    /**
     * @example chargeback
     */
    public string $resource;

    /**
     * The customer the chargeback was raised against.
     * @example customer_78b146a7de7d417e9d68d7e6ef193d18
     */
    public string $customerId;

    /**
     * @example 2020-01-01
     */
    public string $createdAt;

    public bool $testmode;

    /**
     * Dispute lifecycle status: pending, accepted, rejected,
     * evidence_submitted, won, lost.
     * @example pending
     */
    public string $status;

    public Money $amount;

    public Money $settlementAmount;

    /**
     * Gross chargeback amount (incl. tax).
     */
    public Money $total;

    /**
     * Net chargeback amount (excl. tax).
     */
    public Money $subtotal;

    public TaxSummaryCollection $taxSummary;

    public string $reason;

    public ChargebackLinks $links;

    /**
     * The associated order ID
     * @example order_66fc8a40718b46bea50f1a25f456d243
     */
    public ?string $orderId = null;

    /**
     * The associated original order ID
     * @example order_66fc8a40718b46bea50f1a25f456d242
     */
    public string $originalOrderId;
}
