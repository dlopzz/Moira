<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'label'          => ['required', 'string', 'max:100'],
            'street'         => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city'           => ['required', 'string', 'max:100'],
            'state'          => ['required', 'string', 'max:100'],
            'zip_code'       => ['required', 'string', 'max:20'],
            'country'        => ['required', 'string', 'size:2', 'in:AR'],
            'telephone'           => ['required', 'string', 'max:30'],
            'is_default_billing'  => ['boolean'],
            'is_default_shipping' => ['boolean'],
        ];
    }
}
