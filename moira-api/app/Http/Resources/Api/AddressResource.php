<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $customer = $this->resource->customer;

        return [
            'id'                  => $this->id,
            'label'               => $this->label,
            'street'              => $this->street,
            'address_line_2'      => $this->address_line_2,
            'city'                => $this->city,
            'state'               => $this->state,
            'zip_code'            => $this->zip_code,
            'country'             => $this->country,
            'telephone'           => $this->telephone,
            'is_default_billing'  => $customer && $this->id === $customer->default_billing_address_id,
            'is_default_shipping' => $customer && $this->id === $customer->default_shipping_address_id,
        ];
    }
}
