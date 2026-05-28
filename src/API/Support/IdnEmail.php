<?php

declare(strict_types=1);

namespace Vatly\API\Support;

class IdnEmail
{
    /**
     * Convert the domain part of an email to its Punycode (ASCII) form.
     *
     * The local-part is returned unchanged: there is no equivalent ASCII
     * encoding for non-ASCII local-parts, and the Vatly API does not
     * support EAI / SMTPUTF8 (see openapi.yaml).
     *
     * Fail-open: if the input has no '@' or idn_to_ascii fails, return
     * the original value so the caller sees the server's validation error
     * rather than a silently mangled address.
     */
    public static function toAscii(string $email): string
    {
        $at = strrpos($email, '@');
        if ($at === false) {
            return $email;
        }

        $local = substr($email, 0, $at);
        $domain = substr($email, $at + 1);

        if ($domain === '') {
            return $email;
        }

        $asciiDomain = idn_to_ascii($domain, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);
        if ($asciiDomain === false) {
            return $email;
        }

        return $local . '@' . $asciiDomain;
    }

    /**
     * Walk a request payload and normalize any string value at a key named
     * 'email' to its Punycode (ASCII) form. Recurses into nested arrays.
     *
     * @param array<int|string, mixed> $payload
     * @return array<int|string, mixed>
     */
    public static function normalizePayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = self::normalizePayload($value);

                continue;
            }

            if ($key === 'email' && is_string($value)) {
                $payload[$key] = self::toAscii($value);
            }
        }

        return $payload;
    }
}
