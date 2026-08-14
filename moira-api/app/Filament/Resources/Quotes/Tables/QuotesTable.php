<?php

namespace App\Filament\Resources\Quotes\Tables;

use App\Models\Quote;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuotesTable
{
    /** @var array<string, string> */
    private const STATUS_LABELS = [
        Quote::STATUS_ACTIVE     => 'Activo',
        Quote::STATUS_PROCESSING => 'Procesando',
        Quote::STATUS_EXPIRED    => 'Expirado',
        Quote::STATUS_CONVERTED  => 'Convertido',
    ];

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
                        Quote::STATUS_ACTIVE     => 'info',
                        Quote::STATUS_PROCESSING => 'warning',
                        Quote::STATUS_EXPIRED    => 'danger',
                        Quote::STATUS_CONVERTED  => 'success',
                        default                  => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => self::STATUS_LABELS[$state] ?? $state),

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
                    ->options(self::STATUS_LABELS),

                Filter::make('con_items')
                    ->label('Solo con productos')
                    ->query(fn (Builder $query) => $query->whereHas('items')),
            ]);
    }
}
