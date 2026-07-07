<?php

namespace App\Filament\Resources\Orders\Returns\Schemas;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\OrderReturn;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderReturnInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([

                Section::make('Devolución')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('id')->label('N°')->formatStateUsing(fn ($state) => "#{$state}"),

                        TextEntry::make('order_id')
                            ->label('Orden')
                            ->formatStateUsing(fn ($state) => "#{$state}")
                            ->url(fn (OrderReturn $record) => OrderResource::getUrl('view', ['record' => $record->order_id])),

                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state) => OrderReturn::STATUS_COLORS[$state] ?? 'gray')
                            ->formatStateUsing(fn (string $state) => OrderReturn::STATUS_LABELS[$state] ?? $state),

                        TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),

                        TextEntry::make('order.customer.name')->label('Cliente')->placeholder('Invitado'),
                        TextEntry::make('reason')->label('Motivo'),
                        TextEntry::make('telephone')->label('Teléfono'),
                        TextEntry::make('restock')->label('Reingresa stock')->formatStateUsing(fn (bool $state) => $state ? 'Sí' : 'No'),

                        TextEntry::make('restocked_at')->label('Stock repuesto el')->dateTime('d/m/Y H:i')->placeholder('—'),
                        TextEntry::make('refunded_at')->label('Reembolsado el')->dateTime('d/m/Y H:i')->placeholder('—'),

                        TextEntry::make('description')
                            ->label('Descripción')
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->visible(fn (?OrderReturn $record) => filled($record?->description)),
                    ]),

                Section::make('Productos')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('orderItem.product_name')->label('Producto'),
                                TextEntry::make('orderItem.variant_label')->label('Variante')->placeholder('—'),
                                TextEntry::make('quantity')->label('Cantidad devuelta'),
                            ]),
                    ]),

            ]);
    }
}
