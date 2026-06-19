<?php

namespace App\Filament\Resources\Quotes\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),

                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match($state) {
                        'draft'     => 'gray',
                        'expired'   => 'danger',
                        'converted' => 'success',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match($state) {
                        'draft'     => 'Borrador',
                        'expired'   => 'Expirado',
                        'converted' => 'Convertido',
                        default     => $state,
                    }),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft'     => 'Borrador',
                        'expired'   => 'Expirado',
                        'converted' => 'Convertido',
                    ]),
            ]);
    }
}
