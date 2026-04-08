<?php

namespace Vatly\Tests\Endpoints;

use Vatly\API\Exceptions\ApiException;
use Vatly\API\Resources\Order;
use Vatly\API\Resources\OrderCollection;
use Vatly\API\Resources\OrderLine;
use Vatly\API\Types\OrderStatus;
use Vatly\API\VatlyApiClient;

class OrderEndpointTest extends BaseEndpointTest
{
    /** @test
     * @throws ApiException
     */
    public function can_get_order(): void
    {
        $orderId = 'order_dummy_id';
        $responseBodyArray = [
            'id' => $orderId,
            'resource' => 'order',
            'merchantId' => 'merchant_123',
            'customerId' => 'customer_123',
            'testmode' => false,
            'metadata' => [
                'order_id' => '123456',
            ],
            'paymentMethod' => 'ideal',
            'createdAt' => '2023-01-11T10:50:50+02:00',
            'status' => OrderStatus::STATUS_PAID,
            'invoiceNumber' => 'INV 123456',
            'total' => [
                "value" => "96.00",
                "currency" => "EUR",
            ],
            'subtotal' => [
                "value" => "80.00",
                "currency" => "EUR",
            ],
            'taxSummary' => [
                [
                    'taxRate' => ['name' => 'VAT', 'percentage' => 21, 'taxablePercentage' => 100],
                    'amount' => ['value' => '16.00', 'currency' => 'EUR'],
                ],
            ],
            'lines' => [
                [
                    "id" => "order_item_2a46f4c01d3b47979f4d7b3f58c98be7",
                    "resource" => "orderline",
                    "description" => "PDF Book",
                    "quantity" => 1,
                    "basePrice" => [
                        "value" => "80.00",
                        "currency" => "EUR",
                    ],
                    "total" => [
                        "value" => "96.00",
                        "currency" => "EUR",
                    ],
                    "subtotal" => [
                        "value" => "80.00",
                        "currency" => "EUR",
                    ],
                    'taxes' => [
                        [
                            'taxRate' => ['name' => 'VAT', 'percentage' => 21, 'taxablePercentage' => 100],
                            'amount' => ['value' => '16.00', 'currency' => 'EUR'],
                        ],
                    ],
                ],
            ],
            'merchantDetails' => [
                'companyName' => 'Sandorian Consultancy B.V.',
                'streetAndNumber' => 'Korte Leidsedwarsstraat 12',
                'streetAdditional' => '2nd floor',
                'postalCode' => '1017 PN',
                'region' => 'Amsterdam',
                'fullName' => '',
                'city' => 'Amsterdam',
                'country' => 'NL',
                'vatNumber' => 'NL855555555B01',
                'email' => 'office@vatly.com',
            ],
            'customerDetails' => [
                'companyName' => 'JOHN DOE INC.',
                'streetAndNumber' => '112 Main Street',
                'streetAdditional' => '3nd floor',
                'postalCode' => '2424 AB',
                'region' => 'New York',
                'fullName' => 'John Doe',
                'city' => 'New York',
                'country' => 'US',
                'vatNumber' => 'US123456789',
                'email' => 'johndoe@example.com',
            ],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/orders/'.$orderId,
                    'type' => 'application/hal+json',
                ],
                'customer' => [
                    'href' => self::API_ENDPOINT_URL.'/customers/customer_123',
                    'type' => 'application/hal+json',
                ],
                'customerInvoice' => [
                    'href' => self::API_ENDPOINT_URL.'/invoices/invoice_dummy_id',
                    'type' => 'application/pdf',
                ],
            ],
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        /** @var Order $order */
        $order = $this->client->orders->get($orderId, []);

        $this->assertEquals($orderId, $order->id);
        $this->assertEquals('order', $order->resource);
        $this->assertEquals('merchant_123', $order->merchantId);
        $this->assertEquals('customer_123', $order->customerId);
        $this->assertFalse($order->testmode);
        $this->assertEquals('ideal', $order->paymentMethod);
        $this->assertEquals(OrderStatus::STATUS_PAID, $order->status);
        $this->assertEquals('96.00', $order->total->value);
        $this->assertEquals('80.00', $order->subtotal->value);
        $this->assertCount(1, $order->taxSummary->items);
        $this->assertEquals('VAT', $order->taxSummary->items[0]->taxRate->name);
        $this->assertEquals(21, $order->taxSummary->items[0]->taxRate->percentage);
        $this->assertEquals(100, $order->taxSummary->items[0]->taxRate->taxablePercentage);
        $this->assertEquals('16.00', $order->taxSummary->items[0]->amount->value);
        $this->assertEquals('EUR', $order->taxSummary->items[0]->amount->currency);
        $this->assertEquals('INV 123456', $order->invoiceNumber);
        $this->assertEquals('2023-01-11T10:50:50+02:00', $order->createdAt);

        $this->assertEquals('https://api.vatly.com/v1/orders/order_dummy_id', $order->links->self->href);
        $this->assertEquals('application/hal+json', $order->links->self->type);
        $this->assertEquals('https://api.vatly.com/v1/customers/customer_123', $order->links->customer->href);
        $this->assertEquals('application/hal+json', $order->links->customer->type);
        $this->assertEquals('https://api.vatly.com/v1/invoices/invoice_dummy_id', $order->links->customerInvoice->href);
        $this->assertEquals('application/pdf', $order->links->customerInvoice->type);


        $this->assertEquals('Sandorian Consultancy B.V.', $order->merchantDetails->companyName);
        $this->assertEquals('Korte Leidsedwarsstraat 12', $order->merchantDetails->streetAndNumber);
        $this->assertEquals('2nd floor', $order->merchantDetails->streetAdditional);
        $this->assertEquals('1017 PN', $order->merchantDetails->postalCode);
        $this->assertEquals('Amsterdam', $order->merchantDetails->region);
        $this->assertEquals('', $order->merchantDetails->fullName);
        $this->assertEquals('Amsterdam', $order->merchantDetails->city);
        $this->assertEquals('NL', $order->merchantDetails->country);
        $this->assertEquals('NL855555555B01', $order->merchantDetails->vatNumber);
        $this->assertEquals('office@vatly.com', $order->merchantDetails->email);

        $this->assertEquals('JOHN DOE INC.', $order->customerDetails->companyName);
        $this->assertEquals('112 Main Street', $order->customerDetails->streetAndNumber);
        $this->assertEquals('3nd floor', $order->customerDetails->streetAdditional);
        $this->assertEquals('2424 AB', $order->customerDetails->postalCode);
        $this->assertEquals('New York', $order->customerDetails->region);
        $this->assertEquals('John Doe', $order->customerDetails->fullName);
        $this->assertEquals('New York', $order->customerDetails->city);
        $this->assertEquals('US', $order->customerDetails->country);
        $this->assertEquals('US123456789', $order->customerDetails->vatNumber);
        $this->assertEquals('johndoe@example.com', $order->customerDetails->email);

        $this->assertEquals(1, $order->lines()->count());

        /** @var OrderLine $orderLine */
        $orderLine = $order->lines()[0];
        $this->assertEquals('order_item_2a46f4c01d3b47979f4d7b3f58c98be7', $orderLine->id);
        $this->assertEquals('orderline', $orderLine->resource);
        $this->assertEquals('PDF Book', $orderLine->description);
        $this->assertEquals("96.00", $orderLine->total->value);
        $this->assertEquals("80.00", $orderLine->subtotal->value);
        $this->assertEquals("80.00", $orderLine->basePrice->value);
        $this->assertCount(1, $orderLine->taxes->items);
        $this->assertEquals("VAT", $orderLine->taxes->items[0]->taxRate->name);
        $this->assertEquals(21, $orderLine->taxes->items[0]->taxRate->percentage);
        $this->assertEquals(100, $orderLine->taxes->items[0]->taxRate->taxablePercentage);
        $this->assertEquals("16.00", $orderLine->taxes->items[0]->amount->value);
        $this->assertEquals("EUR", $orderLine->taxes->items[0]->amount->currency);
    }

    /** @test */
    public function get_orders_list(): void
    {
        $responseBodyArray = [
            'count' => 2,
            'data' => [
                [
                    'id' => 'order_123',
                    'resource' => 'order',
                ],
                [
                    'id' => 'order_456',
                    'resource' => 'order',
                ],
            ],
            'links' => [
                'self' => [
                    'href' => self::API_ENDPOINT_URL.'/orders',
                    'type' => 'application/hal+json',
                ],
                'next' => [
                    'href' => self::API_ENDPOINT_URL.'/orders?startingAfter=order_next_dummy_id',
                    'type' => 'application/hal+json',
                ],
                'prev' => null,
            ],
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        $orderCollection = $this->client->orders->page();

        $this->assertEquals(2, $orderCollection->count);
        $this->assertCount(2, $orderCollection);
        $this->assertInstanceOf(OrderCollection::class, $orderCollection);
        $this->assertInstanceOf(Order::class, $orderCollection[0]);
        $this->assertInstanceOf(Order::class, $orderCollection[1]);
        $this->assertEquals('order', $orderCollection[0]->resource);
        $this->assertEquals('order', $orderCollection[1]->resource);
        $this->assertEquals('order_123', $orderCollection[0]->id);
        $this->assertEquals('order_456', $orderCollection[1]->id);

        $this->assertEquals(self::API_ENDPOINT_URL.'/orders', $orderCollection->links->self->href);
        $this->assertEquals('application/hal+json', $orderCollection->links->self->type);
        $this->assertEquals(self::API_ENDPOINT_URL.'/orders?startingAfter=order_next_dummy_id', $orderCollection->links->next->href);
        $this->assertEquals('application/hal+json', $orderCollection->links->next->type);
        $this->assertNull($orderCollection->links->prev);

        $this->assertNull($orderCollection->previous());
    }

    /** @test */
    public function can_get_previous_page(): void
    {
        $responseBodyArray = [
            [
                'count' => 1,
                'data' => [
                    ['id' => 'order_123', 'resource' => 'order',],
                ],
                'links' => [
                    'self' => [
                        'href' => self::API_ENDPOINT_URL.'/orders?startingAfter=order_next_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'next' => null,
                    'prev' => [
                        'href' => self::API_ENDPOINT_URL.'/orders?endingBefore=order_previous_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                ],
            ],
            [
                'count' => 1,
                'data' => [
                    ['id' => 'order_456', 'resource' => 'order',],
                ],
                'links' => [
                    'self' => [
                        'href' => self::API_ENDPOINT_URL.'/orders?startingAfter=order_previous_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'next' => [
                        'href' => self::API_ENDPOINT_URL.'/orders?startingAfter=order_next_dummy_id',
                        'type' => 'application/hal+json',
                    ],
                    'prev' => [
                        'href' => self::API_ENDPOINT_URL.'/orders',
                        'type' => 'application/hal+json',
                    ],
                ],
            ],
        ];

        $this->httpClient->setSendReturnCollectionFromArray($responseBodyArray);

        $orderCollection = $this->client->orders->page();

        $previousOrderCollection = $orderCollection->previous();

        $this->assertEquals(1, $previousOrderCollection->count);
        $this->assertCount(1, $previousOrderCollection);
        $this->assertInstanceOf(OrderCollection::class, $previousOrderCollection);

        $order = $previousOrderCollection[0];
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('order', $order->resource);
        $this->assertEquals('order_456', $order->id);
    }

    /** @test */
    public function can_get_order_update_address_link(): void
    {
        $orderId = 'order_dummy_id';
        $responseBodyArray = [
            'href' => self::API_ENDPOINT_URL."/customer/invoices/$orderId?editable=true&signature=1234567890abcdef",
            'type' => 'text/html',
        ];

        $this->httpClient->setSendReturnObjectFromArray($responseBodyArray);

        $response = $this->client->orders->requestAddressUpdateLink($orderId, []);

        $this->assertWasSentOnly(
            VatlyApiClient::HTTP_POST,
            self::API_ENDPOINT_URL.'/orders/'.$orderId.'/request-address-update-link',
            [],
            null
        );
        $this->assertEquals(self::API_ENDPOINT_URL."/customer/invoices/$orderId?editable=true&signature=1234567890abcdef", $response->href);
    }
}
