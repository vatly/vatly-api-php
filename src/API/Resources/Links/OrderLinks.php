<?php

namespace Vatly\API\Resources\Links;

use Vatly\API\Types\Link;

class OrderLinks extends BaseLinksResource
{
    public ?Link $customer = null;
    public ?Link $customerInvoice;
}
