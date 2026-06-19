<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'label'          => $this->label,
            'company'        => $this->company,
            'street'         => $this->street,
            'address_line_2' => $this->address_line_2,
            'city'           => $this->city,
            'state'          => $this->state,
            'zip_code'       => $this->zip_code,
            'country'        => $this->country,
            'telephone'      => $this->telephone,
            'is_default'     => $this->is_default,
        ];
    }
}
