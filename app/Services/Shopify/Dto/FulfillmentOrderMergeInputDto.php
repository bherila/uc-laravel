<?php

declare(strict_types=1);

namespace App\Services\Shopify\Dto;

final readonly class FulfillmentOrderMergeInputDto
{
    /**
     * @param non-empty-list<FulfillmentOrderMergeInputMergeIntent> $mergeIntents
     */
    public function __construct(
        public array $mergeIntents,
    ) {}

    public function toArray(): array
    {
        return [
            'mergeIntents' => array_map(
                fn(FulfillmentOrderMergeInputMergeIntent $mi) => $mi->toArray(),
                $this->mergeIntents
            ),
        ];
    }
}
