<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

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

                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
            ]);
    }
}
