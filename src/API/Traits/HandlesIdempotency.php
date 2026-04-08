<?php

declare(strict_types=1);

namespace Vatly\API\Traits;

use Vatly\API\HttpClient\Idempotency\IdempotencyKeyGeneratorContract;

trait HandlesIdempotency
{
    protected ?IdempotencyKeyGeneratorContract $idempotencyKeyGenerator = null;

    protected ?string $idempotencyKey = null;

    public function setIdempotencyKey(string $idempotencyKey): self
    {
        $this->idempotencyKey = $idempotencyKey;

        return $this;
    }

    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    public function resetIdempotencyKey(): self
    {
        $this->idempotencyKey = null;

        return $this;
    }

    public function setIdempotencyKeyGenerator(IdempotencyKeyGeneratorContract $generator): self
    {
        $this->idempotencyKeyGenerator = $generator;

        return $this;
    }

    public function getIdempotencyKeyGenerator(): ?IdempotencyKeyGeneratorContract
    {
        return $this->idempotencyKeyGenerator;
    }

    public function clearIdempotencyKeyGenerator(): self
    {
        $this->idempotencyKeyGenerator = null;

        return $this;
    }
}
