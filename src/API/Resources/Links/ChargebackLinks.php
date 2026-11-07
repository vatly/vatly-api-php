<?php

namespace Vatly\API\Resources\Links;

use Vatly\API\Types\Link;

class ChargebackLinks extends BaseLinksResource
{
    public Link $originalOrder;

    public ?Link $order;

    /**
     * Customer-portal link to the credit note for this chargeback. The key is named
     * `customerInvoice` for consistency with orders, but on a chargeback it opens the
     * credit note. Null until the chargeback is lost, and absent on older API responses.
     * Signed and valid for 72 hours; after that the customer sees a page to
     * request a new link. Store it if useful, but do not treat it as permanent.
     */
    public ?Link $customerInvoice = null;
}
