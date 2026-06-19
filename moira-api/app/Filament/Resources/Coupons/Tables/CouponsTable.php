<?php

namespace App\Filament\Resources\Coupons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->badge()
                    ->color('warning'),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match($state) {
                        'percentage'    => '% Porcentaje',
                        'fixed'         => '$ Fijo',
                        'free_shipping' => '🚚 Envío gratis',
                        default         => $state,
                    })
                    ->color(fn (string $state) => match($state) {
                        'percentage'    => 'info',
                        'fixed'         => 'primary',
                        'free_shipping' => 'success',
                        default         => 'gray',
                    }),

                TextColumn::make('value')
                    ->label('Descuento')
                    ->formatStateUsing(fn ($state, $record) => match($record->type) {
                        'percentage'    => "{$state}%",
                        'fixed'         => "$ {$state}",
                        'free_shipping' => '—',
                        default         => $state,
                    }),

                TextColumn::make('applies_to')
                    ->label('Aplica sobre')
                    ->formatStateUsing(fn (string $state) => $state === 'products' ? 'Productos' : 'Carrito total')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('used_count')
                    ->label('Usos')
                    ->formatStateUsing(fn ($state, $record) =>
                        $record->max_uses ? "{$state} / {$record->max_uses}" : (string) $state
                    )
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Vence')
                    ->dateTime('d/m/Y')
                    ->placeholder('Sin vencimiento')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'percentage'    => '% Porcentaje',
                        'fixed'         => '$ Monto fijo',
                        'free_shipping' => 'Envío gratis',
                    ]),
                SelectFilter::make('applies_to')
                    ->label('Aplica sobre')
                    ->options([
                        'total'    => 'Total del carrito',
                        'products' => 'Productos específicos',
                    ]),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
