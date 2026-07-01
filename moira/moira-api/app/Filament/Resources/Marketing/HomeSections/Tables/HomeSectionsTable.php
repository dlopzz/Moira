<?php

namespace App\Filament\Resources\Marketing\HomeSections\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HomeSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'hero_slider'  => '🖼 Galería / Slider',
                        'product_tabs' => '🏷 Tabs de productos',
                        'banner'       => '📢 Banner',
                        default        => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'hero_slider'  => 'info',
                        'product_tabs' => 'success',
                        'banner'       => 'warning',
                        default        => 'gray',
                    }),

                TextColumn::make('title')
                    ->label('Título')
                    ->placeholder('—')
                    ->limit(40),

                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Modificada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
