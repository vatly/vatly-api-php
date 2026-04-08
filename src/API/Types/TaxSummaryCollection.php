<?php

namespace Vatly\API\Types;

class TaxSummaryCollection
{
    /**
     * @var TaxSummaryItem[]
     */
    public array $items = [];

    public function __construct(array $items)
    {
        foreach ($items as $item) {
            $this->items[] = TaxSummaryItem::createResourceFromApiResult($item);
        }
    }

    public static function createResourceFromApiResult(array $value): TaxSummaryCollection
    {
        return new TaxSummaryCollection($value);
    }
}
