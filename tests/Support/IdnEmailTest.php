<?php

declare(strict_types=1);

namespace Vatly\Tests\Support;

use PHPUnit\Framework\TestCase;
use Vatly\API\Support\IdnEmail;

class IdnEmailTest extends TestCase
{
    /** @test */
    public function it_passes_ascii_addresses_through_unchanged(): void
    {
        $this->assertSame('user@example.com', IdnEmail::toAscii('user@example.com'));
    }

    /** @test */
    public function it_converts_idn_domain_to_punycode(): void
    {
        $this->assertSame('user@xn--mnchen-3ya.de', IdnEmail::toAscii('user@münchen.de'));
        $this->assertSame('billing@xn--mller-kva.de', IdnEmail::toAscii('billing@müller.de'));
    }

    /** @test */
    public function it_returns_input_unchanged_when_at_sign_is_missing(): void
    {
        $this->assertSame('not-an-email', IdnEmail::toAscii('not-an-email'));
        $this->assertSame('', IdnEmail::toAscii(''));
    }

    /** @test */
    public function it_returns_input_unchanged_when_conversion_fails(): void
    {
        // Empty domain — idn_to_ascii returns false on this. Fail-open: return original.
        $this->assertSame('user@', IdnEmail::toAscii('user@'));
    }

    /** @test */
    public function it_splits_on_the_last_at_sign(): void
    {
        // Quoted local-parts may contain '@'; we should split on the rightmost one.
        $this->assertSame('"foo@bar"@xn--mnchen-3ya.de', IdnEmail::toAscii('"foo@bar"@münchen.de'));
    }

    /** @test */
    public function it_leaves_unicode_local_part_untouched(): void
    {
        // No standardized ASCII encoding for non-ASCII local-parts. Server rejects
        // these per the API spec; the SDK should not silently transform them.
        $this->assertSame('jösé@xn--mnchen-3ya.de', IdnEmail::toAscii('jösé@münchen.de'));
    }

    /** @test */
    public function normalize_payload_walks_nested_structures(): void
    {
        $input = [
            'email' => 'user@münchen.de',
            'billingAddress' => [
                'email' => 'billing@müller.de',
                'city' => 'München',
            ],
            'metadata' => [
                'order_id' => '123',
            ],
        ];

        $expected = [
            'email' => 'user@xn--mnchen-3ya.de',
            'billingAddress' => [
                'email' => 'billing@xn--mller-kva.de',
                'city' => 'München',
            ],
            'metadata' => [
                'order_id' => '123',
            ],
        ];

        $this->assertSame($expected, IdnEmail::normalizePayload($input));
    }

    /** @test */
    public function normalize_payload_only_touches_string_email_values(): void
    {
        $input = [
            'email' => null,
            'inner' => ['email' => 12345],
        ];

        $this->assertSame($input, IdnEmail::normalizePayload($input));
    }

    /** @test */
    public function normalize_payload_leaves_metadata_untouched(): void
    {
        // `metadata` is opaque application-defined data per the OpenAPI spec.
        // The SDK must not rewrite values nested inside it, even when a caller
        // happens to use a key named `email` for their own purposes.
        $input = [
            'email' => 'user@müller.de',
            'metadata' => [
                'email' => 'tracker+user@müller.de',
                'nested' => ['email' => 'should-also-be-left-alone@münchen.de'],
            ],
        ];

        $expected = [
            'email' => 'user@xn--mller-kva.de',
            'metadata' => [
                'email' => 'tracker+user@müller.de',
                'nested' => ['email' => 'should-also-be-left-alone@münchen.de'],
            ],
        ];

        $this->assertSame($expected, IdnEmail::normalizePayload($input));
    }
}
