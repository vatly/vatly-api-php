<?php

declare(strict_types=1);

namespace Vatly\Tests\Webhooks;

use PHPUnit\Framework\TestCase;
use Vatly\API\Exceptions\InvalidSignatureException;
use Vatly\API\Webhooks\WebhookSignatureValidator;

class WebhookSignatureValidatorTest extends TestCase
{
    private string $webhookSecret = 'test_webhook_secret_key';

    private string $payload = '{"id":"webhook_event_Qk8pRtSvWm2NjLhYcZaE","eventName":"order.paid"}';

    public function test_it_validates_a_correct_structured_signature(): void
    {
        $validator = new WebhookSignatureValidator($this->webhookSecret);
        $header = $this->buildHeader(time(), $this->payload, $this->webhookSecret);

        // Should not throw.
        $validator->verify($this->payload, $header);

        $this->assertTrue($validator->isValid($this->payload, $header));
    }

    public function test_it_throws_for_invalid_signature(): void
    {
        $validator = new WebhookSignatureValidator($this->webhookSecret);
        $header = 't='.time().',v1='.str_repeat('0', 64);

        $this->expectException(InvalidSignatureException::class);
        $this->expectExceptionMessage('Invalid webhook signature');

        $validator->verify($this->payload, $header);
    }

    public function test_is_valid_returns_false_for_invalid_signature(): void
    {
        $validator = new WebhookSignatureValidator($this->webhookSecret);
        $header = 't='.time().',v1='.str_repeat('0', 64);

        $this->assertFalse($validator->isValid($this->payload, $header));
    }

    public function test_it_rejects_tampered_payload(): void
    {
        $validator = new WebhookSignatureValidator($this->webhookSecret);
        $tampered = '{"id":"webhook_event_Qk8pRtSvWm2NjLhYcZaE","eventName":"order.canceled"}';
        $header = $this->buildHeader(time(), $this->payload, $this->webhookSecret);

        $this->assertFalse($validator->isValid($tampered, $header));
    }

    public function test_it_rejects_signature_with_wrong_secret(): void
    {
        $validator = new WebhookSignatureValidator($this->webhookSecret);
        $header = $this->buildHeader(time(), $this->payload, 'wrong_secret');

        $this->assertFalse($validator->isValid($this->payload, $header));
    }

    public function test_it_rejects_stale_timestamp_outside_default_tolerance(): void
    {
        $validator = new WebhookSignatureValidator($this->webhookSecret);
        $stale = time() - (WebhookSignatureValidator::DEFAULT_TOLERANCE_SECONDS + 60);
        $header = $this->buildHeader($stale, $this->payload, $this->webhookSecret);

        $this->expectException(InvalidSignatureException::class);
        $this->expectExceptionMessage('outside tolerance window');

        $validator->verify($this->payload, $header);
    }

    public function test_it_rejects_future_timestamp_outside_default_tolerance(): void
    {
        $validator = new WebhookSignatureValidator($this->webhookSecret);
        $future = time() + (WebhookSignatureValidator::DEFAULT_TOLERANCE_SECONDS + 60);
        $header = $this->buildHeader($future, $this->payload, $this->webhookSecret);

        $this->assertFalse($validator->isValid($this->payload, $header));
    }

    public function test_custom_tolerance_seconds_is_honored(): void
    {
        $validator = new WebhookSignatureValidator($this->webhookSecret, toleranceSeconds: 10);
        $stale = time() - 30;
        $header = $this->buildHeader($stale, $this->payload, $this->webhookSecret);

        $this->assertFalse($validator->isValid($this->payload, $header));
    }

    public function test_tolerance_zero_accepts_only_current_second(): void
    {
        $validator = new WebhookSignatureValidator($this->webhookSecret, toleranceSeconds: 0);
        $header = $this->buildHeader(time(), $this->payload, $this->webhookSecret);

        $this->assertTrue($validator->isValid($this->payload, $header));
    }

    public function test_it_rejects_empty_header(): void
    {
        $validator = new WebhookSignatureValidator($this->webhookSecret);

        $this->expectException(InvalidSignatureException::class);
        $this->expectExceptionMessage('Malformed webhook signature header');

        $validator->verify($this->payload, '');
    }

    public function test_it_rejects_malformed_header_missing_timestamp(): void
    {
        $validator = new WebhookSignatureValidator($this->webhookSecret);
        $sig = hash_hmac('sha256', time().'.'.$this->payload, $this->webhookSecret);

        $this->assertFalse($validator->isValid($this->payload, 'v1='.$sig));
    }

    public function test_it_rejects_malformed_header_missing_v1(): void
    {
        $validator = new WebhookSignatureValidator($this->webhookSecret);

        $this->assertFalse($validator->isValid($this->payload, 't='.time()));
    }

    public function test_it_rejects_non_numeric_timestamp(): void
    {
        $validator = new WebhookSignatureValidator($this->webhookSecret);
        $sig = hash_hmac('sha256', '123.'.$this->payload, $this->webhookSecret);

        $this->assertFalse($validator->isValid($this->payload, 't=notanumber,v1='.$sig));
    }

    public function test_it_tolerates_unknown_scheme_keys_alongside_v1(): void
    {
        // Forward-compat: a future header carrying both v1 and v2 must still
        // validate for receivers that only understand v1.
        $validator = new WebhookSignatureValidator($this->webhookSecret);
        $timestamp = time();
        $v1 = hash_hmac('sha256', $timestamp.'.'.$this->payload, $this->webhookSecret);
        $header = "t={$timestamp},v1={$v1},v2=ignored_future_signature";

        $this->assertTrue($validator->isValid($this->payload, $header));
    }

    public function test_calculate_signature_matches_documented_algorithm(): void
    {
        $validator = new WebhookSignatureValidator($this->webhookSecret);
        $timestamp = 1700000000;

        $expected = hash_hmac('sha256', $timestamp.'.'.$this->payload, $this->webhookSecret);

        $this->assertSame($expected, $validator->calculateSignature($this->payload, $timestamp));
    }

    public function test_calculate_signature_pins_a_known_vector(): void
    {
        // Protocol-regression guard: any change to the signing algorithm (hash
        // function, canonicalisation, separator, secret handling) will flip
        // this expected hex string. Update only with intent.
        $validator = new WebhookSignatureValidator('whsec_test_fixture_secret');
        $payload = '{"id":"webhook_event_pin","eventName":"order.paid"}';
        $timestamp = 1700000000;

        $this->assertSame(
            'c6f573e62fac6ebca1e3534ca8019c9c5fe090d638b3b40c23470d5b6f2598ea',
            $validator->calculateSignature($payload, $timestamp),
            'Webhook signing vector changed. If intentional, update the fixture.'
        );
    }

    private function buildHeader(int $timestamp, string $payload, string $secret): string
    {
        $v1 = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return "t={$timestamp},v1={$v1}";
    }
}
