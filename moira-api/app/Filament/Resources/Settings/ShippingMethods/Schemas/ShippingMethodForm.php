<?php

namespace App\Filament\Resources\Settings\ShippingMethods\Schemas;

use App\Models\ShippingMethod;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ShippingMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Información general')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, callable $set) =>
                                $operation === 'create' ? $set('code', Str::slug($state)) : null
                            ),

                        TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->unique(ShippingMethod::class, 'code', ignoreRecord: true)
                            ->maxLength(100)
                            ->helperText('Identificador único. Ej: andreani'),

                        TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->integer()
                            ->default(0)
                            ->minValue(0),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Activo')
                                    ->default(true),

                                Toggle::make('is_simulation')
                                    ->label('Modo simulación')
                                    ->helperText('Usar tarifas de prueba sin llamar a la API real')
                                    ->default(true),
                            ]),

                        Textarea::make('description')
                            ->label('Descripción')
                            ->nullable()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Retiro en sucursal')
                    ->description('Dirección y horario que verá el cliente al elegir retiro en el checkout.')
                    ->columnSpanFull()
                    ->columns(1)
                    ->visible(fn (Get $get): bool => $get('code') === 'retiro_sucursal')
                    ->schema([
                        Textarea::make('config.pickup_address')
                            ->label('Dirección de retiro')
                            ->nullable()
                            ->rows(2),

                        TextInput::make('config.pickup_schedule')
                            ->label('Horario de retiro')
                            ->nullable()
                            ->maxLength(255)
                            ->helperText('Ej: Lun a Vie de 9 a 18 hs'),
                    ]),

                Section::make('Credenciales API')
                    ->description('Datos provistos por Andreani al contratar el servicio. Dejar vacío mientras se usa modo simulación.')
                    ->columnSpanFull()
                    ->hidden(fn (Get $get): bool => $get('code') === 'retiro_sucursal')
                    ->schema([
                        TextInput::make('credentials.username')
                            ->label('Usuario')
                            ->nullable()
                            ->maxLength(255),

                        TextInput::make('credentials.password')
                            ->label('Contraseña')
                            ->nullable()
                            ->password()
                            ->revealable()
                            ->maxLength(255),

                        TextInput::make('credentials.client_number')
                            ->label('Número de cliente')
                            ->nullable()
                            ->maxLength(100)
                            ->helperText('Código de cliente asignado por Andreani'),

                        TextInput::make('credentials.contract_number')
                            ->label('Número de contrato')
                            ->nullable()
                            ->maxLength(100)
                            ->helperText('Número de contrato o sucursal de origen'),
                    ])
                    ->columns(2),

                Section::make('Configuración de tarifas')
                    ->columnSpanFull()
                    ->columns(2)
                    ->hidden(fn (Get $get): bool => $get('code') === 'retiro_sucursal')
                    ->schema([
                        TextInput::make('config.markup_percentage')
                            ->label('Recargo (%)')
                            ->numeric()
                            ->nullable()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->helperText('Porcentaje adicional sobre la tarifa de Andreani'),

                        TextInput::make('config.default_weight_grams')
                            ->label('Peso estimado por ítem (g)')
                            ->numeric()
                            ->integer()
                            ->nullable()
                            ->default(500)
                            ->suffix('g')
                            ->helperText('Usado cuando el producto no tiene peso definido'),
                    ]),
            ]);
    }
}
