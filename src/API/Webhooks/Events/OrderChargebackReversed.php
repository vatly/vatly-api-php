<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks\Events;

use Vatly\API\Resources\Chargeback as ApiChargeback;
use Vatly\API\Types\Money;
use Vatly\API\Types\TaxSummaryCollection;
use Vatly\API\Types\WebhookEventName;

/**
 * Event representing a previously-received chargeback being reversed at Vatly.
 *
 * Counterpart to {@see OrderChargebackReceived}: dispatched so drivers can
 * reinstate access they suspended on the original chargeback. The envelope's
 * `entityId` is the order ID.
 *
 * Enriched via `GetChargeback` when that action is wired (same rationale as
 * {@see OrderChargebackReceived}); otherwise built from the sparse webhook
 * payload. A built-in reaction uses the enriched status to flip the local
 * chargeback row to its reversed state.
 *
 * @immutable
 */
class OrderChargebackReversed
{
    public const VATLY_EVENT_NAME = WebhookEventName::ORDER_CHARGEBACK_REVERSED;

    public function __construct(
        public string $orderId,
        public string $chargebackId,
        public string $originalOrderId,
        public ?string $reason = null,
        public string $customerId = '',
        public string $status = '',
        public ?Money $total = null,
        public ?Money $subtotal = null,
        public ?TaxSummaryCollection $taxSummary = null,
        public string $currency = '',
    ) {
        //
    }

    public static function fromWebhook(WebhookReceived $webhook): self
    {
        return new self(
            orderId: $webhook->entityId,
            chargebackId: $webhook->object['id'] ?? '',
            originalOrderId: $webhook->object['originalOrderId'] ?? $webhook->entityId,
            reason: $webhook->object['reason'] ?? null,
        );
    }

    public static function fromApiChargeback(ApiChargeback $chargeback): self
    {
        return new self(
            orderId: $chargeback->originalOrderId,
            chargebackId: $chargeback->id,
            originalOrderId: $chargeback->originalOrderId,
            reason: $chargeback->reason !== '' ? $chargeback->reason : null,
            customerId: $chargeback->customerId,
            status: $chargeback->status,
            total: $chargeback->total,
            subtotal: $chargeback->subtotal,
            taxSummary: $chargeback->taxSummary,
            currency: $chargeback->total->currency,
        );
    }
}
