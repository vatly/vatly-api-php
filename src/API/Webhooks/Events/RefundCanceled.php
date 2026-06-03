<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks\Events;

use Vatly\API\Resources\Refund as ApiRefund;
use Vatly\API\Types\TaxSummaryCollection;
use Vatly\API\Types\WebhookEventName;

/**
 * Event representing a pending refund being canceled before it is sent at Vatly.
 *
 * Carries the full tax breakdown so consumers can reconcile a local refund row
 * without a follow-up API call. The webhook event factory enriches via
 * `GetRefund` before dispatching — mirroring the `order.paid` pattern, since
 * refund tax data is compliance-critical and must be authoritative.
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
        public int $total,
        public int $subtotal,
        public TaxSummaryCollection $taxSummary,
        public string $currency,
        public string $originalOrderId,
    ) {
        //
    }

    public static function fromApiRefund(ApiRefund $refund): self
    {
        return new self(
            customerId: $refund->customerId,
            refundId: $refund->id,
            status: $refund->status,
            total: $refund->total->toCents(),
            subtotal: $refund->subtotal->toCents(),
            taxSummary: $refund->taxSummary,
            currency: $refund->total->currency,
            originalOrderId: $refund->originalOrderId,
        );
    }
}
