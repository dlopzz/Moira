<?php

namespace App\Filament\Resources\Settings\PaymentMethods\Schemas;

use App\Models\PaymentMethod;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PaymentMethodForm
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
                            ->unique(PaymentMethod::class, 'code', ignoreRecord: true)
                            ->maxLength(100)
                            ->helperText('Identificador único. Ej: payway'),

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

                                Toggle::make('is_sandbox')
                                    ->label('Usar Sandbox')
                                    ->helperText('Desactivar para usar las credenciales de producción')
                                    ->default(true),
                            ]),
                    ]),

                Section::make('Credenciales Sandbox')
                    ->description('Credenciales del ambiente de pruebas. Las encontrás en developers.payway.com.ar → tu proyecto → Modo Sandbox.')
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        TextInput::make('credentials.sandbox_public_key')
                            ->label('API Key pública (Sandbox)')
                            ->nullable()
                            ->maxLength(500)
                            ->helperText('Se usa en el frontend para tokenizar la tarjeta'),

                        TextInput::make('credentials.sandbox_private_key')
                            ->label('API Key secreta (Sandbox)')
                            ->nullable()
                            ->password()
                            ->revealable()
                            ->maxLength(500)
                            ->helperText('Solo se usa en el servidor — se codifica en Base64 para Authorization: Basic'),
                    ]),

                Section::make('Credenciales Producción')
                    ->description('Credenciales del ambiente productivo. Las encontrás en developers.payway.com.ar → tu proyecto → Modo Producción.')
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        TextInput::make('credentials.production_public_key')
                            ->label('API Key pública (Producción)')
                            ->nullable()
                            ->maxLength(500)
                            ->helperText('Se usa en el frontend para tokenizar la tarjeta'),

                        TextInput::make('credentials.production_private_key')
                            ->label('API Key secreta (Producción)')
                            ->nullable()
                            ->password()
                            ->revealable()
                            ->maxLength(500)
                            ->helperText('Solo se usa en el servidor — se codifica en Base64 para Authorization: Basic'),
                    ]),

                Section::make('Configuración de endpoints')
                    ->description('URLs de la API y del JS SDK. Actualizalas solo si PayWay cambia sus endpoints.')
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        TextInput::make('config.endpoint_sandbox')
                            ->label('Endpoint Sandbox')
                            ->nullable()
                            ->maxLength(500)
                            ->placeholder('https://api-sandbox.payway.com.ar')
                            ->helperText('Base URL del ambiente de pruebas (sin /api/v2/)'),

                        TextInput::make('config.endpoint_production')
                            ->label('Endpoint Producción')
                            ->nullable()
                            ->maxLength(500)
                            ->placeholder('https://api.payway.com.ar')
                            ->helperText('Base URL del ambiente productivo (sin /api/v2/)'),

                        TextInput::make('config.js_sdk_url')
                            ->label('URL del JS SDK (decidir.js)')
                            ->nullable()
                            ->maxLength(500)
                            ->placeholder('https://ventasonline.payway.com.ar/static/v2.6.4/decidir.js')
                            ->helperText('Script de tokenización que se carga en el checkout del cliente'),
                    ]),
            ]);
    }
}
