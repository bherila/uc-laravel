<?php

declare(strict_types=1);

namespace App\Services\Shopify\Dto;

final readonly class FulfillmentOrderLineItemInput
{
    public function __construct(
        public ?string $id = null,
        public ?string $fulfillmentOrderLineItemId = null,
        public ?int $remainingQuantity = null,
        public ?int $quantity = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'quantity' => $this->quantity,
        ], fn($value) => $value !== null);
    }
}
