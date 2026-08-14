<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\Role;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Se respeta el rol elegido en el form. Solo se fuerza Admin si no vino
        // ninguno: el rol define el acceso al panel y no puede quedar vacío.
        $data['role'] ??= Role::Admin;

        return $data;
    }
}
