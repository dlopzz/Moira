<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Models\Review;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('rating')
                    ->label('Puntuación')
                    ->formatStateUsing(fn (?int $state) => $state ? str_repeat('⭐', $state) . " ({$state})" : '—')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Título')
                    ->placeholder('—')
                    ->limit(40),

                TextColumn::make('body')
                    ->label('Comentario')
                    ->placeholder('—')
                    ->limit(60)
                    ->wrap(),

                TextColumn::make('submitted_at')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => $state ? 'Enviada' : 'Sin enviar')
                    ->badge()
                    ->color(fn ($state) => $state ? 'warning' : 'gray')
                    ->sortable(),

                IconColumn::make('is_approved')
                    ->label('Aprobada')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('submitted')
                    ->label('Estado de envío')
                    ->nullable()
                    ->attribute('submitted_at')
                    ->trueLabel('Enviadas')
                    ->falseLabel('Sin enviar'),

                TernaryFilter::make('is_approved')
                    ->label('Aprobación')
                    ->trueLabel('Aprobadas')
                    ->falseLabel('No aprobadas'),

                SelectFilter::make('rating')
                    ->label('Puntuación')
                    ->options([1 => '1 ⭐', 2 => '2 ⭐', 3 => '3 ⭐', 4 => '4 ⭐', 5 => '5 ⭐']),
            ])
            ->recordActions([
                Action::make('toggle_approved')
                    ->label(fn (Review $record) => $record->is_approved ? 'Rechazar' : 'Aprobar')
                    ->icon(fn (Review $record) => $record->is_approved ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Review $record) => $record->is_approved ? 'danger' : 'success')
                    ->visible(fn (Review $record) => $record->submitted_at !== null)
                    ->action(fn (Review $record) => $record->update(['is_approved' => ! $record->is_approved])),

                DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    BulkAction::make('bulk_approve')
                        ->label('Aprobar seleccionadas')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_approved' => true])),

                    BulkAction::make('bulk_reject')
                        ->label('Rechazar seleccionadas')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_approved' => false])),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
