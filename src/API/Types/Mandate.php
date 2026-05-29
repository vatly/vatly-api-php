<?php

namespace Vatly\API\Types;

class Mandate
{
    /**
     * Normalized, provider-agnostic payment method category:
     * `card`, `sepa_debit`, `paypal`, or `bacs_debit`.
     *
     * @example card
     */
    public string $method;

    /**
     * Customer-facing masked identifier for the payment method on file,
     * e.g. last 4 of a card ("4242") or a masked IBAN ("NL91****4300").
     *
     * May briefly be null for freshly-subscribed customers until a follow-up sync resolves it.
     *
     * @example 4242
     */
    public ?string $maskedIdentifier;

    public function __construct(string $method, ?string $maskedIdentifier)
    {
        $this->method = $method;
        $this->maskedIdentifier = $maskedIdentifier;
    }

    public static function createResourceFromApiResult($value): Mandate
    {
        if (is_array($value)) {
            $value = (object) $value;
        }

        return new self($value->method, $value->maskedIdentifier ?? null);
    }
}
