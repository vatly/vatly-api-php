<?php

declare(strict_types=1);

namespace Vatly\API\Webhooks\Events;

use Vatly\API\Resources\Chargeback as ApiChargeback;
use Vatly\API\Types\Money;
use Vatly\API\Types\TaxSummaryCollection;
use Vatly\API\Types\WebhookEventName;

/**
 * Event representing a chargeback being received against an order at Vatly.
 *
 * Dispatched so drivers can react — e.g. suspend access tied to the order and
 * open the dispute window. The envelope's `entityId` is the order ID, so the
 * driver can locate the affected order directly.
 *
 * The signed webhook payload is the authoritative snapshot — its `object` is
 * byte-identical to the `GET /chargebacks/{id}` body — so
 * {@see \Vatly\API\Webhooks\WebhookEventFactory} hydrates this event straight
 * from that payload (no follow-up API GET). It therefore carries the customer
 * id, dispute status, and the full tax breakdown — enough to persist a local
 * row and reconcile the reversed VAT. {@see self::fromWebhook()} remains as a
 * sparse fallback for builders that only have the envelope (`orderId`,
 * `chargebackId`, `originalOrderId`, `reason`).
 *
 * @immutable
 */
class OrderChargebackReceived
{
    public const VATLY_EVENT_NAME = WebhookEventName::ORDER_CHARGEBACK_RECEIVED;

    public function __construct(
        public string $orderId,
        public string $chargebackId,
        public string $originalOrderId,
        public bool $testmode,
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
            testmode: $webhook->testmode,
            reason: $webhook->object['reason'] ?? null,
        );
    }

    public static function fromApiChargeback(ApiChargeback $chargeback): self
    {
        return new self(
            orderId: $chargeback->originalOrderId,
            chargebackId: $chargeback->id,
            originalOrderId: $chargeback->originalOrderId,
            testmode: $chargeback->testmode,
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
