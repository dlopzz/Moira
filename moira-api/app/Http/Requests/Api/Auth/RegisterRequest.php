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
            'date_of_birth' => ['required', 'date', 'before:-16 years'],
            'password'      => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-zA-Z])(?=.*[0-9])/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'first_name.required'    => 'El nombre es obligatorio.',
            'first_name.max'         => 'El nombre no puede superar los 100 caracteres.',
            'last_name.required'     => 'El apellido es obligatorio.',
            'last_name.max'          => 'El apellido no puede superar los 100 caracteres.',
            'email.required'         => 'El email es obligatorio.',
            'email.email'            => 'Ingresá un email válido.',
            'email.max'              => 'El email no puede superar los 255 caracteres.',
            'email.unique'           => 'Ya existe una cuenta con ese email.',
            'date_of_birth.required' => 'La fecha de nacimiento es obligatoria.',
            'date_of_birth.date'     => 'Ingresá una fecha de nacimiento válida.',
            'date_of_birth.before'   => 'Debés ser mayor de 16 años para registrarte.',
            'password.required'      => 'La contraseña es obligatoria.',
            'password.min'           => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'     => 'Las contraseñas no coinciden.',
            'password.regex'         => 'La contraseña debe contener al menos una letra y un número.',
        ];
    }
}
