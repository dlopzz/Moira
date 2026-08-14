<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class CustomerForm
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

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(Customer::class, 'email', ignoreRecord: true)
                    ->maxLength(255),

                DatePicker::make('date_of_birth')
                    ->label('Fecha de nacimiento')
                    ->nullable()
                    ->maxDate(now()->subYears(13)),

                Textarea::make('notes')
                    ->label('Notas internas')
                    ->nullable()
                    ->rows(3)
                    ->columnSpanFull(),

                // customers.password es NOT NULL, así que sin este campo el alta
                // desde el admin fallaba con una violación de constraint. Es
                // opcional: si se deja vacío, CreateCustomer guarda una contraseña
                // aleatoria y el cliente entra definiendo la suya con el botón
                // "Enviar reseteo de contraseña" de la ficha.
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->rule(Password::default())
                    ->dehydrated(fn ($state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'create'
                        ? 'Opcional. Si la dejás vacía, mandale el reseteo de contraseña desde la ficha del cliente.'
                        : 'Dejá en blanco para mantener la contraseña actual.'),

                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
            ]);
    }
}
