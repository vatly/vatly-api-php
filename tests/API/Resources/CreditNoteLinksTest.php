<?php

declare(strict_types=1);

namespace Vatly\Tests\API\Resources;

use Vatly\API\Resources\BaseResource;
use Vatly\API\Resources\Chargeback;
use Vatly\API\Resources\Refund;
use Vatly\API\Resources\ResourceFactory;
use Vatly\Tests\BaseTestCase;

class CreditNoteLinksTest extends BaseTestCase
{
    /**
     * @return array<string, array{class-string<BaseResource>}>
     */
    public static function resourceProvider(): array
    {
        return [
            'refund' => [Refund::class],
            'chargeback' => [Chargeback::class],
        ];
    }

    /**
     * @dataProvider resourceProvider
     *
     * @param class-string<Refund|Chargeback> $resourceClass
     */
    public function test_it_hydrates_the_credit_note_link(string $resourceClass): void
    {
        $resource = $this->hydrate($resourceClass, [
            'self' => ['href' => 'https://api.vatly.com/v1/x/1', 'type' => 'application/hal+json'],
            'customerInvoice' => ['href' => 'https://portal.vatly.com/invoices/ord_credit_1?signature=abc', 'type' => 'text/html'],
        ]);

        $this->assertNotNull($resource->links->customerInvoice);
        $this->assertSame('https://portal.vatly.com/invoices/ord_credit_1?signature=abc', $resource->links->customerInvoice->href);
        $this->assertSame('text/html', $resource->links->customerInvoice->type);
    }

    /**
     * @dataProvider resourceProvider
     *
     * @param class-string<Refund|Chargeback> $resourceClass
     */
    public function test_the_credit_note_link_is_null_when_the_api_sends_null(string $resourceClass): void
    {
        $resource = $this->hydrate($resourceClass, [
            'self' => ['href' => 'https://api.vatly.com/v1/x/1', 'type' => 'application/hal+json'],
            'customerInvoice' => null,
        ]);

        $this->assertNull($resource->links->customerInvoice);
    }

    /**
     * @dataProvider resourceProvider
     *
     * @param class-string<Refund|Chargeback> $resourceClass
     */
    public function test_the_credit_note_link_is_null_when_an_older_api_omits_the_key(string $resourceClass): void
    {
        $resource = $this->hydrate($resourceClass, [
            'self' => ['href' => 'https://api.vatly.com/v1/x/1', 'type' => 'application/hal+json'],
        ]);

        $this->assertNull($resource->links->customerInvoice);
    }

    /**
     * @param class-string<Refund|Chargeback> $resourceClass
     * @param array<string, mixed> $links
     */
    private function hydrate(string $resourceClass, array $links): Refund|Chargeback
    {
        $apiResult = json_decode((string) json_encode(['id' => 'x_1', 'links' => $links]));

        $resource = ResourceFactory::createResourceFromApiResult($apiResult, new $resourceClass($this->client));
        assert($resource instanceof Refund || $resource instanceof Chargeback);

        return $resource;
    }
}
