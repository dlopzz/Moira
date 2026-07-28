<?php

namespace App\Http\Resources\Api;

use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $subtotal     = $this->getSubtotal();
        $shippingCost = (float) $this->shipping_cost;
        $discount     = (float) $this->discount_amount;
        $total        = $this->getTotal();

        $isPickup = $this->shipping_method_code === 'retiro_sucursal';
        $pickup   = $isPickup ? ShippingMethod::where('code', 'retiro_sucursal')->first() : null;

        return [
            'id'         => $this->id,
            'status'     => $this->status,
            'expires_at' => $this->expires_at?->toISOString(),
            'coupon_code' => $this->coupon_code,
            'shipping' => [
                'code'            => $this->shipping_method_code,
                'label'           => $this->shipping_method_label,
                'price'           => $shippingCost,
                'is_pickup'       => $isPickup,
                'pickup_address'  => $pickup?->configValue('pickup_address'),
                'pickup_schedule' => $pickup?->configValue('pickup_schedule'),
            ],
            'items'   => CartItemResource::collection($this->items),
            'summary' => [
                'items_count'   => $this->items->sum('quantity'),
                'subtotal'      => round($subtotal, 2),
                'shipping_cost' => round($shippingCost, 2),
                'discount'      => round($discount, 2),
                'total'         => round($total, 2),
            ],
        ];
    }
}
