<?php

declare(strict_types=1);

namespace Vatly\Tests\API\HttpClient;

use Vatly\API\Exceptions\DebuggingNotSupportedException;
use Vatly\API\HttpClient\HttpClientInterface;

class SpyHttpClient implements HttpClientInterface
{
    protected ?object $sendReturn;
    protected array $recordedSends = [];

    protected array $sendReturnCollection = [];

    public function send(
        string $httpMethod,
        string $url,
        array $headers,
        ?string $httpBody
    ): ?object {
        // If we have a collection of send returns, use the first one and remove it from the collection
        if (count($this->sendReturnCollection) > 0) {
            $this->sendReturn = array_shift($this->sendReturnCollection);
        }

        $this->recordSend($httpMethod, $url, $headers, $httpBody);

        return $this->sendReturn;
    }

    public function versionString(): string
    {
        return 'SpyHttpClient/007';
    }

    /**
     * @param object|array|string $value
     * @return $this
     */
    public function setSendReturnObject($value): self
    {
        if (! is_object($value)) {
            throw new \InvalidArgumentException('Value must be an object or array.');
        }
        $this->sendReturn = $value;

        return $this;
    }

    public function setSendReturnNull(): self
    {
        $this->sendReturn = null;

        return $this;
    }

    public function setSendReturnObjectFromArray(array $value): self
    {
        return $this->setSendReturnObject(json_decode(json_encode($value)));
    }

    public function setSendReturnCollection(array $value): self
    {
        foreach ($value as $item) {
            if (! is_object($item)) {
                ray($item);

                throw new \InvalidArgumentException('Value must be an object or array.');
            }
        }

        $this->sendReturnCollection = $value;

        return $this;
    }

    public function setSendReturnCollectionFromArray(array $value): self
    {
        $items = array_map(function ($item) {
            return json_decode(json_encode($item));
        }, $value);

        return $this->setSendReturnCollection($items);
    }



    public function setSendReturnObjectFromString(string $value): self
    {
        return $this->setSendReturnObject(json_decode($value));
    }

    public function wasSent(
        string $httpMethod,
        string $url,
        array $headers,
        ?string $httpBody
    ): bool {
        $sanitizedHttpBody = ($httpBody === null)
            ? null
            : json_encode(json_decode(
                $httpBody,
                false,
                512,
                JSON_THROW_ON_ERROR
            ));

        return count(array_filter($this->recordedSends, function ($item) use ($httpMethod, $url, $headers, $sanitizedHttpBody) {
            return $item['httpMethod'] === $httpMethod
                && $item['url'] === $url
                && $item['headers'] === $headers
                && $item['httpBody'] === $sanitizedHttpBody;
        })) > 0;
    }

    /**
     * Whether the provided call was the only call that was made, and made only once.
     *
     * @param string $httpMethod
     * @param string $url
     * @param array $headers
     * @param string $httpBody
     * @return bool
     */
    public function wasSentOnly(
        string $httpMethod,
        string $url,
        array $headers,
        ?string $httpBody
    ): bool {
        if ($this->countRecordedSends() !== 1) {
            return false;
        }

        return $this->wasSent(
            $httpMethod,
            $url,
            $headers,
            $httpBody,
        );
    }

    public function countRecordedSends(): int
    {
        return count($this->recordedSends);
    }

    public function recordedSends(): array
    {
        return $this->recordedSends;
    }

    private function recordSend(
        string $httpMethod,
        string $url,
        array $headers,
        ?string $httpBody
    ): void {
        $sanitizedHeaders = array_filter($headers, function ($key) {
            return ! in_array($key, [
                'Accept',
                'Authorization',
                'User-Agent',
                'Content-Type',
                'X-Vatly-Client-Info',
                'Idempotency-Key',
            ]);
        }, ARRAY_FILTER_USE_KEY);

        $this->recordedSends[] = [
            'time' => time(),
            'httpMethod' => $httpMethod,
            'url' => $url,
            'headers' => $sanitizedHeaders,
            'allHeaders' => $headers,
            'httpBody' => $httpBody,
        ];
    }

    public function lastSentHeaders(): array
    {
        if (empty($this->recordedSends)) {
            return [];
        }

        return end($this->recordedSends)['allHeaders'];
    }

    public function supportsDebugging(): bool
    {
        return false;
    }

    /**
     * @throws \Vatly\API\Exceptions\DebuggingNotSupportedException
     */
    public function enableDebugging(): void
    {
        throw DebuggingNotSupportedException::new();
    }

    /**
     * @throws \Vatly\API\Exceptions\DebuggingNotSupportedException
     */
    public function disableDebugging(): void
    {
        throw DebuggingNotSupportedException::new();
    }
}
