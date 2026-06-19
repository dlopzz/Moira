<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'name'       => $this->name,
            'email'      => $this->email,
            'dob'        => $this->date_of_birth?->toDateString(),
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
