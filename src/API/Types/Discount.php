<?php

declare(strict_types=1);

namespace Vatly\API\Types;

/**
 * A discount applied to an order (`discounts[]` on an order and on each of
 * its lines).
 *
 * On the order, each discount is listed once with the amount it takes off all
 * lines added up. On a line, it is that line's share; the line's `subtotal`,
 * `taxes` and `total` are already net of it. Match a line's share to the
 * order's entry by `id`.
 *
 * `amount` always excludes VAT, also when the products' prices include VAT:
 * €10.00 off a €121.00 price at 21% reads 8.26.
 *
 * @immutable
 */
class Discount
{
    public function __construct(
        /**
         * Identifies this discount on this order. It names one application,
         * not a reusable coupon: every order and every subscription renewal
         * gets new ids. `null` only on discounts applied before ids existed.
         *
         * @example discount_KKYYk6SohKNdSqKyHAzZJ2
         */
        public ?string $id,
        /**
         * The label sent with the discount, if any.
         *
         * @example Black Friday
         */
        public ?string $description,
        /**
         * `percentage` or `fixed_amount` (see {@see DiscountType}). More values
         * may be added; treat an unknown value as a discount of an unknown
         * kind. `null` only on discounts applied before it was recorded.
         *
         * @example percentage
         */
        public ?string $discountType,
        /**
         * The percentage taken off, as a decimal string, for a `percentage`
         * discount. `null` for a `fixed_amount` discount.
         *
         * @example 20.00
         */
        public ?string $percentage,
        /**
         * The amount taken off, excluding VAT.
         */
        public Money $amount,
    ) {
        //
    }

    /**
     * @param object|array<string, mixed> $value
     */
    public static function createResourceFromApiResult($value): self
    {
        $value = (array) $value;

        return new self(
            $value['id'] ?? null,
            $value['description'] ?? null,
            $value['discountType'] ?? null,
            isset($value['percentage']) ? (string) $value['percentage'] : null,
            Money::createResourceFromApiResult($value['amount']),
        );
    }

    /**
     * @param iterable<object|array<string, mixed>>|null $values
     * @return Discount[]
     */
    public static function createListFromApiResult($values): array
    {
        $discounts = [];

        foreach ($values ?? [] as $value) {
            $discounts[] = self::createResourceFromApiResult($value);
        }

        return $discounts;
    }
}
