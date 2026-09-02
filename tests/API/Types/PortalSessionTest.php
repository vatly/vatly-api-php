<?php

declare(strict_types=1);

namespace Vatly\Tests\API\Types;

use Vatly\API\Types\PortalSession;
use Vatly\Tests\BaseTestCase;

class PortalSessionTest extends BaseTestCase
{
    /** @test */
    public function it_hydrates_from_a_stdclass_api_result(): void
    {
        $value = json_decode((string) json_encode([
            'url' => 'https://billing.vatly.com/authenticate?credential=abc',
            'expiresAt' => '2026-09-01T12:15:00Z',
            'returnUrl' => 'https://merchant.example/account/billing',
        ]));

        $session = PortalSession::createResourceFromApiResult($value);

        $this->assertSame('https://billing.vatly.com/authenticate?credential=abc', $session->url);
        $this->assertSame('2026-09-01T12:15:00Z', $session->expiresAt);
        $this->assertSame('https://merchant.example/account/billing', $session->returnUrl);
    }

    /** @test */
    public function it_hydrates_a_null_return_url(): void
    {
        $session = PortalSession::createResourceFromApiResult([
            'url' => 'https://billing.vatly.com/authenticate?credential=abc',
            'expiresAt' => '2026-09-01T12:15:00Z',
            'returnUrl' => null,
        ]);

        $this->assertNull($session->returnUrl);
    }

    /** @test */
    public function it_defaults_return_url_to_null_when_absent(): void
    {
        $session = PortalSession::createResourceFromApiResult([
            'url' => 'https://billing.vatly.com/authenticate?credential=abc',
            'expiresAt' => '2026-09-01T12:15:00Z',
        ]);

        $this->assertNull($session->returnUrl);
    }
}
