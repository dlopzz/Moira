<?php

namespace App\Filament\Resources\Marketing\ContactMessages\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->formatStateUsing(fn ($record) => $record->name.' '.$record->last_name)
                    ->searchable(['name', 'last_name']),

                TextColumn::make('email')
                    ->label('Email')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('message')
                    ->label('Mensaje')
                    ->limit(60)
                    ->placeholder('—'),

                TextColumn::make('is_read')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Leído' : 'No leído')
                    ->color(fn (bool $state) => $state ? 'success' : 'warning'),

                TextColumn::make('created_at')
                    ->label('Recibido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                Action::make('toggle_read')
                    ->label(fn ($record) => $record->is_read ? 'Marcar no leído' : 'Marcar leído')
                    ->icon(fn ($record) => $record->is_read ? 'heroicon-o-envelope' : 'heroicon-o-envelope-open')
                    ->action(fn ($record) => $record->update(['is_read' => ! $record->is_read]))
                    ->requiresConfirmation(false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
