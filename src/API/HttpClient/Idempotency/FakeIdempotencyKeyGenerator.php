<?php

declare(strict_types=1);

namespace Vatly\API\HttpClient\Idempotency;

/**
 * @internal This package's test helper; not covered by semver.
 */
class FakeIdempotencyKeyGenerator implements IdempotencyKeyGeneratorContract
{
    protected string $fakeKey = 'fake-idempotency-key';

    public function setFakeKey(string $key): self
    {
        $this->fakeKey = $key;

        return $this;
    }

    public function generate(): string
    {
        return $this->fakeKey;
    }
}
