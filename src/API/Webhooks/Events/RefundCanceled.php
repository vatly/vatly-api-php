<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks\Events;

use Vatly\API\Resources\Refund as ApiRefund;
use Vatly\API\Types\Money;
use Vatly\API\Types\TaxSummaryCollection;
use Vatly\API\Types\WebhookEventName;

/**
 * Event representing a pending refund being canceled before it is sent at Vatly.
 *
 * Carries the full tax breakdown so consumers can reconcile a local refund row
 * without a follow-up API call. The webhook event factory hydrates this event
 * straight from the fat, signed refund webhook payload (byte-identical to
 * `GET /v1/refunds/{id}`), so the breakdown is authoritative with zero network
 * round-trip, mirroring the `order.paid` pattern.
 *
 * @immutable
 */
class RefundCanceled
{
    public const VATLY_EVENT_NAME = WebhookEventName::REFUND_CANCELED;

    public function __construct(
        public string $customerId,
        public string $refundId,
        public string $status,
        public Money $total,
        public Money $subtotal,
        public TaxSummaryCollection $taxSummary,
        public string $originalOrderId,
        public bool $testmode,
        /**
         * The credit order Vatly created from this refund, when one exists; its
         * customerInvoice is the credit note. Typically null for a canceled
         * refund (no credit order materialized), but carried for consistency with
         * the other refund events and so consumers never need a follow-up GetRefund.
         */
        public ?string $orderId = null,
    ) {
        //
    }

    public static function fromApiRefund(ApiRefund $refund): self
    {
        return new self(
            customerId: $refund->customerId,
            refundId: $refund->id,
            status: $refund->status,
            total: $refund->total,
            subtotal: $refund->subtotal,
            taxSummary: $refund->taxSummary,
            originalOrderId: $refund->originalOrderId,
            testmode: $refund->testmode,
            orderId: $refund->orderId,
        );
    }
}
