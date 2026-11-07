<?php

namespace Vatly\API\Resources\Links;

use Vatly\API\Types\Link;

class OrderLinks extends BaseLinksResource
{
    public ?Link $customer = null;
    /**
     * Customer-portal link to the order's invoice. Null until the invoice exists.
     * Signed and valid for 72 hours; after that the customer sees a page to
     * request a new link. Store it if useful, but do not treat it as permanent.
     */
    public ?Link $customerInvoice = null;
}
