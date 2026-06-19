<?php

namespace App\Services\Shipping;

readonly class ShippingRate
{
    public function __construct(
        public string $code,
        public string $label,
        public float $price,
        public string $estimatedDays,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'code'           => $this->code,
            'label'          => $this->label,
            'price'          => $this->price,
            'estimated_days' => $this->estimatedDays,
        ];
    }
}
