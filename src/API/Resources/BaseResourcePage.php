<?php

declare(strict_types=1);

namespace Vatly\API\Resources;

use ArrayObject;
use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Links\PaginationLinks;
use Vatly\API\VatlyApiClient;

abstract class BaseResourcePage extends ArrayObject
{
    /**
     * Total number of retrieved objects.
     *
     * @var int
     */
    public int $count;

    /**
     * @var PaginationLinks|null
     */
    public $links;

    /**
     * @var BaseResource[]
     */
    public array $data;

    protected VatlyApiClient $apiClient;

    /**
     * @param VatlyApiClient $apiClient
     * @param int $count
     * @param PaginationLinks|null $links
     */
    final public function __construct(VatlyApiClient $apiClient, $count, $links)
    {
        $this->apiClient = $apiClient;
        $this->count = $count;
        $this->links = $links;
        parent::__construct();
    }


    /**
     * @return string|null
     */
    abstract public function getCollectionResourceName(): ?string;



    abstract protected function createResourceObject();

    /**
     * Return the next set of resources when available
     *
     * @return BaseResourcePage|null
     * @throws ApiException
     */
    final public function next(): ?BaseResourcePage
    {
        if (! $this->hasNext()) {
            return null;
        }

        $result = $this->apiClient->performHttpCall(VatlyApiClient::HTTP_GET, $this->apiPathFromLink($this->links->next->href));

        $collection = new static($this->apiClient, $result->count, $result->links);

        foreach ($result->data as $dataResult) {
            $collection[] = ResourceFactory::createResourceFromApiResult($dataResult, $this->createResourceObject());
        }

        return $collection;
    }

    /**
     * Return the previous set of resources when available
     *
     * @return BaseResourcePage|null
     * @throws ApiException
     */
    final public function previous(): ?BaseResourcePage
    {
        if (! $this->hasPrevious()) {
            return null;
        }

        $result = $this->apiClient->performHttpCall(VatlyApiClient::HTTP_GET, $this->apiPathFromLink($this->links->prev->href));

        $collection = new static($this->apiClient, $result->count, $result->links);

        foreach ($result->data as $dataResult) {
            $collection[] = ResourceFactory::createResourceFromApiResult($dataResult, $this->createResourceObject());
        }

        return $collection;
    }

    /**
     * The API path of a pagination link, sent to the configured API endpoint.
     * Only the path and query of the link are used, never its host.
     *
     * @throws ApiException
     */
    private function apiPathFromLink(string $href): string
    {
        $path = (string) parse_url($href, PHP_URL_PATH);
        $query = parse_url($href, PHP_URL_QUERY);
        $versionPrefix = '/' . VatlyApiClient::API_VERSION . '/';
        $position = strpos($path, $versionPrefix);

        if ($position === false) {
            throw new ApiException("Unexpected pagination link: '{$href}'.");
        }

        $apiPath = substr($path, $position + strlen($versionPrefix));

        foreach (explode('/', $apiPath) as $segment) {
            if (in_array(rawurldecode($segment), ['', '.', '..'], true)) {
                throw new ApiException("Unexpected pagination link: '{$href}'.");
            }
        }

        return is_string($query) && $query !== '' ? "{$apiPath}?{$query}" : $apiPath;
    }

    /**
     * Determine whether the collection has a next page available.
     *
     * @return bool
     */
    public function hasNext(): bool
    {
        return isset($this->links->next, $this->links->next->href);
    }

    /**
     * Determine whether the collection has a previous page available.
     *
     * @return bool
     */
    public function hasPrevious(): bool
    {
        return isset($this->links->prev, $this->links->prev->href);
    }

    private function pointsToNextItems(): bool
    {
        return ! str_contains($this->links->self->href, 'endingBefore');
    }

    /**
     * @throws ApiException
     */
    public function autoPagingIterator(): \Generator
    {
        $page = $this;
        $goToNextPage = $this->pointsToNextItems();
        do {
            foreach ($page as $item) {
                yield $item;
            }
        } while ($page = $goToNextPage ? $page->next() : $page->previous());
    }
}
