<?php

declare(strict_types=1);

namespace Vatly\API\Types;

/**
 * A short-lived, single-use entry link to Vatly's hosted customer portal.
 *
 * Redirect the customer's browser to {@see self::$url}. The link expires after
 * roughly 15 minutes and can be consumed once. It is credential-bearing, so do
 * not cache or log it.
 *
 * @immutable
 */
class PortalSession
{
    public function __construct(
        /**
         * Single-use HTTPS URL to redirect the customer to.
         */
        public string $url,
        /**
         * Expiry of the one-time entry link (ISO 8601), not of the resulting
         * browser session.
         */
        public string $expiresAt,
        /**
         * The validated absolute HTTPS return URL supplied in the request, or
         * `null`.
         */
        public ?string $returnUrl = null,
    ) {
        //
    }

    /**
     * Hydrate from an API result (a `stdClass`, as the API client decodes a
     * response) or an array.
     *
     * @param object|array<string, mixed> $value
     */
    public static function createResourceFromApiResult($value): self
    {
        if (is_array($value)) {
            $value = (object) $value;
        }

        return new self(
            url: $value->url,
            expiresAt: $value->expiresAt,
            returnUrl: $value->returnUrl ?? null,
        );
    }
}
