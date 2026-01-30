<?php

declare(strict_types=1);

namespace App\Services\Shopify\Dto;

final readonly class FulfillmentOrderMergeInputMergeIntent
{
    /**
     * @param list<FulfillmentOrderLineItemInput> $fulfillmentOrderLineItems
     */
    public function __construct(
        public string $fulfillmentOrderId,
        public array $fulfillmentOrderLineItems = [],
    ) {}

    public function toArray(): array
    {
        return [
            'fulfillmentOrderId' => $this->fulfillmentOrderId,
            'fulfillmentOrderLineItems' => array_map(
                fn(FulfillmentOrderLineItemInput $li) => $li->toArray(),
                $this->fulfillmentOrderLineItems
            ),
        ];
    }
}
