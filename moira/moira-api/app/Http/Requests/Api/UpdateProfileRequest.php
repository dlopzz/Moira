<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * El email NO se puede editar acá. Es el identificador de la cuenta y está
     * ligado a email_verified_at: si se pudiera cambiar desde el perfil, la
     * cuenta quedaría marcada como verificada con una dirección que nadie
     * verificó. Cambiarlo requiere un flujo aparte con confirmación en la
     * dirección nueva.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name'    => ['required', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before:-13 years'],
        ];
    }
}
