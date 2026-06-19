<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CartItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'product_id'    => $this->product_id,
            'variant_id'    => $this->variant_id,
            'variant_label' => $this->variant_label,
            'name'          => $this->product_name,
            'sku'           => $this->product_sku,
            'image'         => $this->product_image ? Storage::url($this->product_image) : null,
            'unit_price'    => (float) $this->unit_price,
            'quantity'      => $this->quantity,
            'subtotal'      => (float) $this->subtotal,
        ];
    }
}
