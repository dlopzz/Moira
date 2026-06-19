<?php

namespace App\Filament\Resources\Categories\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Productos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('price')
                    ->label('Precio')
                    ->money('ARS')
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Agregar producto')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'sku'])
                    ->recordSelect(fn (Select $select) => $select
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->name . ($record->sku ? " — {$record->sku}" : ''))
                    ),
            ])
            ->recordActions([
                DetachAction::make()->label('Quitar'),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    DetachBulkAction::make()->label('Quitar seleccionados'),
                ]),
            ]);
    }
}
