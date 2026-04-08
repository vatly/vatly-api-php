<?php

declare(strict_types=1);

namespace Vatly\Tests\API\HttpClient\Idempotency;

use PHPUnit\Framework\TestCase;
use Vatly\API\HttpClient\Idempotency\DefaultIdempotencyKeyGenerator;

class DefaultIdempotencyKeyGeneratorTest extends TestCase
{
    /** @test */
    public function generates_a_16_character_string()
    {
        $generator = new DefaultIdempotencyKeyGenerator();

        $key = $generator->generate();

        $this->assertIsString($key);
        $this->assertEquals(16, strlen($key));
    }

    /** @test */
    public function generates_unique_keys()
    {
        $generator = new DefaultIdempotencyKeyGenerator();

        $key1 = $generator->generate();
        $key2 = $generator->generate();

        $this->assertNotEquals($key1, $key2);
    }
}
