<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('first_name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(100),

                TextInput::make('last_name')
                    ->label('Apellido')
                    ->required()
                    ->maxLength(100),

                TextInput::make('username')
                    ->label('Usuario')
                    ->required()
                    ->maxLength(50)
                    ->unique(User::class, 'username', ignoreRecord: true)
                    ->alphaNum()
                    ->helperText('Solo letras y números, sin espacios.'),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(User::class, 'email', ignoreRecord: true)
                    ->maxLength(255),

                Select::make('role')
                    ->label('Rol')
                    ->options(collect(Role::adminRoles())->mapWithKeys(
                        fn (Role $r) => [$r->value => $r->label()]
                    ))
                    ->default(Role::Admin->value)
                    ->required()
                    ->disabled(fn ($record) => $record?->role === Role::SuperAdmin && auth()->id() !== $record?->id)
                    ->helperText('Solo el Super Admin puede gestionar otros Super Admins.'),

                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->rule(Password::default())
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'Dejá en blanco para mantener la contraseña actual.' : null),
            ]);
    }
}
