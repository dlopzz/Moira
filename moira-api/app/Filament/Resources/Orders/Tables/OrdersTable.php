<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class OrdersTable
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
                        'pending'    => 'warning',
                        'paid'       => 'info',
                        'processing' => 'info',
                        'shipped'    => 'primary',
                        'delivered'  => 'success',
                        'cancelled'  => 'danger',
                        'refunded'   => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match($state) {
                        'pending'    => 'Pendiente',
                        'paid'       => 'Pagado',
                        'processing' => 'En proceso',
                        'shipped'    => 'Enviado',
                        'delivered'  => 'Entregado',
                        'cancelled'  => 'Cancelado',
                        'refunded'   => 'Reembolsado',
                        default      => $state,
                    }),

                TextColumn::make('paymentMethod.name')
                    ->label('Pago')
                    ->placeholder('—'),

                TextColumn::make('shippingMethod.name')
                    ->label('Envío')
                    ->placeholder('—'),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('ARS')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending'    => 'Pendiente',
                        'paid'       => 'Pagado',
                        'processing' => 'En proceso',
                        'shipped'    => 'Enviado',
                        'delivered'  => 'Entregado',
                        'cancelled'  => 'Cancelado',
                        'refunded'   => 'Reembolsado',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
