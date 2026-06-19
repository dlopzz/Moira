<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => str_pad((string) $this->id, 8, '0', STR_PAD_LEFT),
            'status' => $this->status,
            'shipping_address' => $this->shipping_address,
            'subtotal' => (float) $this->subtotal,
            'shipping_cost' => (float) $this->shipping_cost,
            'discount' => (float) $this->discount,
            'coupon_code' => $this->coupon_code,
            'total' => (float) $this->total,
            'created_at' => $this->created_at->toISOString(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product_name,
                'variant_label' => $item->variant_label,
                'unit_price' => (float) $item->unit_price,
                'quantity' => $item->quantity,
                'subtotal' => (float) $item->subtotal,
            ])),
        ];
    }
}
