<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Models\Coupon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('code')
                    ->label('Código')
                    ->required()
                    ->unique(Coupon::class, 'code', ignoreRecord: true)
                    ->maxLength(50)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) =>
                        $set('code', strtoupper(trim($state)))
                    )
                    ->placeholder('Ej: VERANO20'),

                Select::make('type')
                    ->label('Tipo')
                    ->required()
                    ->live()
                    ->options([
                        'percentage'    => '% Porcentaje',
                        'fixed'         => '$ Monto fijo',
                        'free_shipping' => '🚚 Envío gratis',
                    ]),

                TextInput::make('value')
                    ->label(fn ($get) => $get('type') === 'percentage' ? 'Descuento (%)' : 'Descuento ($)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(fn ($get) => $get('type') === 'percentage' ? 100 : null)
                    ->prefix(fn ($get) => $get('type') === 'percentage' ? '%' : '$')
                    ->nullable()
                    ->visible(fn ($get) => $get('type') !== 'free_shipping')
                    ->required(fn ($get) => $get('type') !== 'free_shipping'),

                Select::make('applies_to')
                    ->label('Aplica sobre')
                    ->required()
                    ->live()
                    ->options([
                        'total'    => 'Total del carrito',
                        'products' => 'Productos específicos',
                    ])
                    ->default('total'),

                Select::make('products')
                    ->label('Productos')
                    ->relationship('products', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull()
                    ->visible(fn ($get) => $get('applies_to') === 'products')
                    ->required(fn ($get) => $get('applies_to') === 'products'),

                TextInput::make('min_order_amount')
                    ->label('Monto mínimo de compra')
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0)
                    ->nullable(),

                TextInput::make('max_uses')
                    ->label('Usos máximos totales')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->nullable()
                    ->placeholder('Sin límite'),

                TextInput::make('uses_per_customer')
                    ->label('Usos por cliente')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->nullable()
                    ->placeholder('Sin límite'),

                DateTimePicker::make('starts_at')
                    ->label('Válido desde')
                    ->nullable()
                    ->displayFormat('d/m/Y H:i'),

                DateTimePicker::make('expires_at')
                    ->label('Válido hasta')
                    ->nullable()
                    ->displayFormat('d/m/Y H:i')
                    ->after('starts_at'),

                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->columnSpanFull(),
            ]);
    }
}
