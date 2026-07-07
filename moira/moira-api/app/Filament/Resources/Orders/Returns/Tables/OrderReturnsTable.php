<?php

namespace App\Filament\Resources\Orders\Returns\Tables;

use App\Models\OrderReturn;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),

                TextColumn::make('order_id')
                    ->label('Orden')
                    ->formatStateUsing(fn ($state) => "#{$state}")
                    ->sortable(),

                TextColumn::make('order.customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->placeholder('Invitado'),

                TextColumn::make('reason')->label('Motivo'),

                TextColumn::make('telephone')->label('Teléfono'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => OrderReturn::STATUS_COLORS[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state) => OrderReturn::STATUS_LABELS[$state] ?? $state),

                IconColumn::make('restock')->label('Repone stock')->boolean(),

                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(OrderReturn::STATUS_LABELS),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
