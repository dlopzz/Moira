<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Str;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // La contraseña es opcional en el form, pero la columna es NOT NULL: se
        // guarda una aleatoria que nadie conoce, y el cliente define la suya con
        // el reseteo. El cast 'hashed' del modelo la hashea al guardar.
        if (blank($data['password'] ?? null)) {
            $data['password'] = Str::random(32);
        }

        // El login exige email verificado. Un alta hecha a mano por el admin ya
        // es una verificación: sin esto el cliente recién creado no podría entrar.
        $data['email_verified_at'] ??= now();

        return $data;
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::End;
    }
}
