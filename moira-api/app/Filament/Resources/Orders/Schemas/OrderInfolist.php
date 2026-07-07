<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Resources\Orders\Returns\OrderReturnResource;
use App\Models\Order;
use App\Models\OrderReturn;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([

                Section::make('Orden')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('id')
                            ->label('N° de orden')
                            ->formatStateUsing(fn ($state) => "#{$state}"),

                        TextEntry::make('created_at')
                            ->label('Fecha')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state) => Order::STATUS_COLORS[$state] ?? 'gray')
                            ->formatStateUsing(fn (string $state) => Order::STATUS_LABELS[$state] ?? $state),

                        TextEntry::make('customer.name')
                            ->label('Cliente')
                            ->placeholder('Invitado'),

                        TextEntry::make('customer.email')
                            ->label('Email')
                            ->placeholder('—'),
                    ]),

                Section::make('Productos')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->columns(5)
                            ->schema([
                                TextEntry::make('product_name')->label('Producto'),
                                TextEntry::make('variant_label')->label('Variante')->placeholder('—'),
                                TextEntry::make('unit_price')->label('Precio unit.')->money('ARS'),
                                TextEntry::make('quantity')->label('Cant.'),
                                TextEntry::make('subtotal')->label('Subtotal')->money('ARS'),
                            ]),
                    ]),

                Section::make('Pago')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('paymentMethod.name')->label('Método')->placeholder('—'),
                        TextEntry::make('transaction.status')->label('Estado transacción')->placeholder('—'),
                        TextEntry::make('transaction.gateway_transaction_id')->label('ID transacción')->placeholder('—'),
                        TextEntry::make('transaction.card_brand')->label('Marca de tarjeta')->placeholder('—'),
                        TextEntry::make('transaction.installments')->label('Cuotas')->placeholder('—'),
                        TextEntry::make('transaction.amount_cents')
                            ->label('Monto cobrado')
                            ->formatStateUsing(fn ($state) => $state ? '$'.number_format($state / 100, 2, ',', '.') : '—'),
                    ]),

                Section::make('Envío')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('shipping_method_label')->label('Método')->placeholder('—'),
                        TextEntry::make('shipping_cost')->label('Costo envío')->money('ARS'),
                        TextEntry::make('tracking_number')->label('N° de seguimiento')->placeholder('—'),
                        TextEntry::make('shipped_at')->label('Enviado el')->dateTime('d/m/Y H:i')->placeholder('—'),
                        TextEntry::make('shipping_address')
                            ->label('Dirección')
                            ->formatStateUsing(fn ($state) => is_array($state)
                                ? (collect($state)->only(['street', 'address_line_2', 'city', 'state', 'zip_code'])->filter()->implode(', ') ?: '—')
                                : '—')
                            ->columnSpanFull(),
                    ]),

                Section::make('Totales')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('subtotal')->label('Subtotal')->money('ARS'),
                        TextEntry::make('shipping_cost')->label('Envío')->money('ARS'),
                        TextEntry::make('discount')->label('Descuento')->money('ARS'),
                        TextEntry::make('coupon_code')->label('Cupón')->placeholder('—'),
                        TextEntry::make('total')->label('Total')->money('ARS')->weight('bold'),
                    ]),

                Section::make('Devoluciones')
                    ->columnSpanFull()
                    ->visible(fn (?Order $record) => $record?->returns->isNotEmpty())
                    ->schema([
                        RepeatableEntry::make('returns')
                            ->label('')
                            ->columns(4)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('N°')
                                    ->formatStateUsing(fn ($state) => "#{$state}")
                                    ->url(fn (OrderReturn $record) => OrderReturnResource::getUrl('view', ['record' => $record->id])),
                                TextEntry::make('reason')->label('Motivo'),
                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->color(fn (string $state) => OrderReturn::STATUS_COLORS[$state] ?? 'gray')
                                    ->formatStateUsing(fn (string $state) => OrderReturn::STATUS_LABELS[$state] ?? $state),
                                TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                            ]),
                    ]),

                TextEntry::make('notes')
                    ->label('Notas')
                    ->placeholder('—')
                    ->columnSpanFull()
                    ->visible(fn (?Order $record) => filled($record?->notes)),
            ]);
    }
}
