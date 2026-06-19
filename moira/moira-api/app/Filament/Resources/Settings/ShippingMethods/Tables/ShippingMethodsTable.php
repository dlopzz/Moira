<?php

namespace App\Filament\Resources\Settings\ShippingMethods\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShippingMethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('code')->label('Código')->badge()->color('gray'),
                TextColumn::make('price')->label('Costo')->money('ARS')->sortable(),
                TextColumn::make('description')->label('Descripción')->placeholder('—')->limit(50),
                IconColumn::make('is_active')->label('Activo')->boolean(),
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
