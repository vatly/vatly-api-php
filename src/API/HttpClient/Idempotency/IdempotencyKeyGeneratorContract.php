<?php

declare(strict_types=1);

namespace Vatly\API\HttpClient\Idempotency;

interface IdempotencyKeyGeneratorContract
{
    public function generate(): string;
}
