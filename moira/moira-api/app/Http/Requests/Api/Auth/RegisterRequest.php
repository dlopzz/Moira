<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name'    => ['required', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email', 'max:255', 'unique:customers,email'],
            'date_of_birth' => ['required', 'date', 'before:-13 years'],
            'password'      => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-zA-Z])(?=.*[0-9])/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'password.regex' => 'La contraseña debe contener al menos una letra y un número.',
        ];
    }
}
