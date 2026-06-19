<?php

namespace App\Filament\Resources\Reviews\Schemas;

use App\Models\Customer;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('product_id')
                    ->label('Producto')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('customer_id')
                    ->label('Cliente')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('rating')
                    ->label('Puntuación')
                    ->options([
                        1 => '⭐ 1 — Muy malo',
                        2 => '⭐⭐ 2 — Malo',
                        3 => '⭐⭐⭐ 3 — Regular',
                        4 => '⭐⭐⭐⭐ 4 — Bueno',
                        5 => '⭐⭐⭐⭐⭐ 5 — Excelente',
                    ])
                    ->required(),

                Toggle::make('is_approved')
                    ->label('Aprobada')
                    ->default(false),

                TextInput::make('title')
                    ->label('Título')
                    ->nullable()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('body')
                    ->label('Comentario')
                    ->rows(4)
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }
}
