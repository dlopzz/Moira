<?php

namespace App\Filament\Resources\Marketing\Newsletter\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NewsletterSubscribersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('Email')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->unsubscribed_at ? 'Desuscripto' : 'Activo')
                    ->color(fn ($record) => $record->unsubscribed_at ? 'gray' : 'success'),

                TextColumn::make('created_at')
                    ->label('Suscripto el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('unsubscribed_at')
                    ->label('Desuscripto el')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
