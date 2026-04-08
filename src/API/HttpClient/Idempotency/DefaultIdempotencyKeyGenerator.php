<?php

declare(strict_types=1);

namespace Vatly\API\HttpClient\Idempotency;

class DefaultIdempotencyKeyGenerator implements IdempotencyKeyGeneratorContract
{
    public function generate(): string
    {
        return substr(base64_encode(random_bytes(24)), 0, 16);
    }
}
