<?php

namespace Vatly\Tests\Endpoints;

use Vatly\API\Resources\OneOffProduct;
use Vatly\API\Resources\OneOffProductCollection;
use Vatly\API\VatlyApiClient;

class OneOffProductEndpointTest extends BaseEndpointTest
{
    /** @test */
    public function it_can_create_a_one_off_product()
    {
        $productId = 'one_off_product_Vr8kQdFhSrG4Y3DnfsdqH';

        $this->httpClient->setSendReturnObjectFromArray([
            'id' => $productId,
            'resource' => 'one_off_product',
            'name' => 'Premium License',
            'description' => 'Lifetime access to all premium features',
            'basePrice' => ['value' => '299.00', 'currency' => 'EUR'],
            'testmode' => false,
            'status' => 'pending',
            'createdAt' => '2024-01-15T10:30:00Z',
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/one-off-products/'.$productId,
                    'type' => 'application/json',
                ],
            ],
        ]);

        /** @var OneOffProduct $product */
        $product = $this->client->oneOffProducts->create([
            'name' => 'Premium License',
            'description' => 'Lifetime access to all premium features',
            'basePrice' => ['value' => '299.00', 'currency' => 'EUR'],
            'productType' => 'saas',
        ]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/one-off-products',
            [],
            '{"name":"Premium License","description":"Lifetime access to all premium features","basePrice":{"value":"299.00","currency":"EUR"},"productType":"saas"}'
        );

        $this->assertInstanceOf(OneOffProduct::class, $product);
        $this->assertEquals($productId, $product->id);
        $this->assertEquals('Premium License', $product->name);
        $this->assertEquals('299.00', $product->basePrice->value);
        $this->assertEquals('pending', $product->status);
    }

    /** @test */
    public function it_exposes_tax_behavior_and_archived_at()
    {
        $productId = 'one_off_product_Vr8kQdFhSrG4Y3DnfsdqH';

        $this->httpClient->setSendReturnObjectFromArray([
            'id' => $productId,
            'resource' => 'one_off_product',
            'name' => 'Premium License',
            'description' => 'Lifetime access',
            'basePrice' => ['value' => '299.00', 'currency' => 'EUR'],
            'taxBehavior' => 'inclusive',
            'productType' => 'saas',
            'testmode' => false,
            'status' => 'active',
            'archivedAt' => null,
            'pendingUpdates' => null,
            'updateStatus' => null,
            'createdAt' => '2024-01-15T10:30:00Z',
            'links' => ['self' => ['href' => self::API_ENDPOINT_URL.'/one-off-products/'.$productId, 'type' => 'application/json']],
        ]);

        /** @var OneOffProduct $product */
        $product = $this->client->oneOffProducts->get($productId);

        $this->assertEquals('inclusive', $product->taxBehavior);
        $this->assertEquals('saas', $product->productType);
        $this->assertNull($product->archivedAt);
        $this->assertNull($product->pendingUpdates);
        $this->assertNull($product->updateStatus);
        $this->assertFalse($product->isArchived());
    }

    /** @test */
    public function it_can_update_a_one_off_product()
    {
        $productId = 'one_off_product_Vr8kQdFhSrG4Y3DnfsdqH';

        $this->httpClient->setSendReturnObjectFromArray([
            'id' => $productId,
            'resource' => 'one_off_product',
            'name' => 'Premium License',
            'description' => 'Lifetime access',
            'basePrice' => ['value' => '299.00', 'currency' => 'EUR'],
            'taxBehavior' => 'exclusive',
            'productType' => 'saas',
            'testmode' => false,
            'status' => 'active',
            'archivedAt' => null,
            'pendingUpdates' => ['basePrice' => ['value' => '349.00', 'currency' => 'EUR']],
            'updateStatus' => 'pending',
            'createdAt' => '2024-01-15T10:30:00Z',
            'links' => ['self' => ['href' => self::API_ENDPOINT_URL.'/one-off-products/'.$productId, 'type' => 'application/json']],
        ]);

        /** @var OneOffProduct $product */
        $product = $this->client->oneOffProducts->update($productId, [
            'name' => 'Premium License v2',
            'basePrice' => ['value' => '349.00', 'currency' => 'EUR'],
        ]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_PATCH,
            self::API_ENDPOINT_URL.'/one-off-products/'.$productId,
            [],
            '{"name":"Premium License v2","basePrice":{"value":"349.00","currency":"EUR"}}'
        );

        $this->assertInstanceOf(OneOffProduct::class, $product);
        $this->assertEquals('pending', $product->updateStatus);
        $this->assertEquals('349.00', $product->pendingUpdates->basePrice->value);
    }

    /** @test */
    public function it_can_archive_a_one_off_product()
    {
        $productId = 'one_off_product_Vr8kQdFhSrG4Y3DnfsdqH';

        $this->httpClient->setSendReturnNull();

        $this->client->oneOffProducts->archive($productId);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/one-off-products/'.$productId.'/archive',
            [],
            null
        );
    }

    /** @test */
    public function it_can_unarchive_a_one_off_product()
    {
        $productId = 'one_off_product_Vr8kQdFhSrG4Y3DnfsdqH';

        $this->httpClient->setSendReturnObjectFromArray([
            'id' => $productId,
            'resource' => 'one_off_product',
            'name' => 'Premium License',
            'description' => 'Lifetime access',
            'basePrice' => ['value' => '299.00', 'currency' => 'EUR'],
            'taxBehavior' => 'exclusive',
            'productType' => 'saas',
            'testmode' => false,
            'status' => 'active',
            'archivedAt' => null,
            'createdAt' => '2024-01-15T10:30:00Z',
            'links' => ['self' => ['href' => self::API_ENDPOINT_URL.'/one-off-products/'.$productId, 'type' => 'application/json']],
        ]);

        /** @var OneOffProduct $product */
        $product = $this->client->oneOffProducts->unarchive($productId);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_DELETE,
            self::API_ENDPOINT_URL.'/one-off-products/'.$productId.'/archive',
            [],
            null
        );

        $this->assertInstanceOf(OneOffProduct::class, $product);
        $this->assertNull($product->archivedAt);
        $this->assertFalse($product->isArchived());
    }

    /** @test */
    public function it_can_list_archived_one_off_products_with_include_archived_filter()
    {
        $this->httpClient->setSendReturnObjectFromArray([
            'count' => 0,
            'data' => [],
            'links' => [
                'self' => ['href' => self::API_ENDPOINT_URL.'/one-off-products?includeArchived=true', 'type' => 'application/json'],
                'next' => null,
                'prev' => null,
            ],
        ]);

        $this->client->oneOffProducts->page(null, null, null, ['includeArchived' => true]);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_GET,
            self::API_ENDPOINT_URL.'/one-off-products?includeArchived=true',
            [],
            null
        );
    }

    /** @test */
    public function can_get_one_off_product()
    {
        $productId = 'one_off_product_78b146a7de7d417e9d68d7e6ef193d18';

        $responseBodyArray = [
            'id' => $productId,
            'resource' => 'one_off_product',
            'name' => 'Test product',
            'description' => 'Test product description',
            'basePrice' => [
                'value' => '10.00',
                'currency' => 'EUR',
            ],
            'testmode' => false,
            'status' => 'active',
            'createdAt' => '2023-01-11T10:50:50+02:00',
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL. '/one-off-products/' . $productId,
                    'type' => 'application/hal+json',
                ],
            ],
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        /** @var OneOffProduct $product */
        $product = $this->client->oneOffProducts->get($productId);

        $this->assertEquals($productId, $product->id);
        $this->assertEquals('one_off_product', $product->resource);
        $this->assertEquals('Test product', $product->name);
        $this->assertEquals('Test product description', $product->description);
        $this->assertEquals('10.00', $product->basePrice->value);
        $this->assertEquals('EUR', $product->basePrice->currency);
        $this->assertFalse($product->testmode);
        $this->assertEquals('active', $product->status);
        $this->assertEquals('2023-01-11T10:50:50+02:00', $product->createdAt);

        $this->assertEquals(self::API_ENDPOINT_URL. '/one-off-products/' . $productId, $product->links->self->href);
        $this->assertEquals('application/hal+json', $product->links->self->type);
    }

    /** @test */
    public function can_list_one_off_products()
    {
        $responseBodyArray = [
            'count' => 2,
            'data' => [
                ['id' => 'one_off_product_123', 'resource' => 'one_off_product'],
                ['id' => 'one_off_product_456', 'resource' => 'one_off_product'],
            ],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/one-off-products',
                    'type' => 'application/hal+json',
                ],
                'next' => [
                    'href' => self::API_ENDPOINT_URL.'/one-off-products?startingAfter=one_off_product_next_dummy_id',
                    'type' => 'application/hal+json',
                ],
                'prev' => [
                    'href' => self::API_ENDPOINT_URL.'/one-off-products?endingBefore=one_off_product_previous_dummy_id',
                    'type' => 'application/hal+json',
                ],
            ],
        ];


        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        $productCollection = $this->client->oneOffProducts->page();

        $this->assertEquals(2, $productCollection->count);
        $this->assertCount(2, $productCollection);
        $this->assertInstanceOf(OneOffProductCollection::class, $productCollection);
        $this->assertInstanceOf(OneOffProduct::class, $productCollection[0]);
        $this->assertInstanceOf(OneOffProduct::class, $productCollection[1]);

        $this->assertEquals('one_off_product_123', $productCollection[0]->id);
        $this->assertEquals('one_off_product_456', $productCollection[1]->id);

        $this->assertEquals(self::API_ENDPOINT_URL.'/one-off-products', $productCollection->links->self->href);
        $this->assertEquals('application/hal+json', $productCollection->links->self->type);
        $this->assertEquals(self::API_ENDPOINT_URL.'/one-off-products?startingAfter=one_off_product_next_dummy_id', $productCollection->links->next->href);
        $this->assertEquals('application/hal+json', $productCollection->links->next->type);
        $this->assertEquals(self::API_ENDPOINT_URL.'/one-off-products?endingBefore=one_off_product_previous_dummy_id', $productCollection->links->prev->href);
        $this->assertEquals('application/hal+json', $productCollection->links->prev->type);
    }

    /** @test */
    public function can_get_next_page_of_one_off_products()
    {
        $responseBodyArrayCollection = [
            [
                'count' => 2,
                'data' => [
                    ['id' => 'one_off_product_123', 'resource' => 'one_off_product',],
                    ['id' => 'one_off_product_456', 'resource' => 'one_off_product',],
                ],
                'links' => [
                    'self' => [
                        'href' => self::API_ENDPOINT_URL . '/one-off-products',
                        'type' => 'application/hal+json',
                    ],
                    'next' => [
                        'href' => self::API_ENDPOINT_URL . '/one-off-products?startingAfter=one_off_product_next_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'prev' => null,
                ],
            ],
            [
                'count' => 1,
                'data' => [
                    ['id' => 'one_off_product_789', 'resource' => 'one_off_product',],
                ],
                'links' => [
                    'self' => [
                        'href' => self::API_ENDPOINT_URL . '/one-off-products?startingAfter=one_off_product_next_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'next' => null,
                    'prev' => [
                        'href' => self::API_ENDPOINT_URL . '/one-off-products',
                        'type' => 'application/hal+json',
                    ],
                ],
            ],
        ];

        $this->httpClient->setSendReturnCollectionFromArray($responseBodyArrayCollection);

        $productCollection = $this->client->oneOffProducts->page();

        $nextProductCollection = $productCollection->next();

        $this->assertEquals(1, $nextProductCollection->count);
        $this->assertCount(1, $nextProductCollection);
        $this->assertInstanceOf(OneOffProductCollection::class, $nextProductCollection);

        $product = $nextProductCollection[0];
        $this->assertInstanceOf(OneOffProduct::class, $product);
        $this->assertEquals('one_off_product_789', $product->id);

        $this->assertNull($nextProductCollection->next());
    }
}
