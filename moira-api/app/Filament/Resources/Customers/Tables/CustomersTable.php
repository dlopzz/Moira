<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name']),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('addresses_count')
                    ->label('Direcciones')
                    ->counts('addresses')
                    ->sortable(),

                TextColumn::make('reviews_count')
                    ->label('Reseñas')
                    ->counts('reviews')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Suspendidos'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),

                // Suspender en vez de eliminar: borrar un cliente deja su email
                // bloqueado por el índice único de la tabla (ni siquiera puede
                // volver a registrarse), y se pierden sus órdenes de vista.
                Action::make('suspender')
                    ->label('Suspender')
                    ->icon(Heroicon::NoSymbol)
                    ->color('danger')
                    ->visible(fn (Customer $record): bool => $record->is_active && ! $record->trashed())
                    ->requiresConfirmation()
                    ->modalHeading('Suspender cliente')
                    ->modalDescription('No va a poder iniciar sesión ni comprar. Se cierran sus sesiones abiertas. Sus datos y órdenes se conservan y podés reactivarlo cuando quieras.')
                    ->action(fn (Customer $record) => $record->suspend()),

                Action::make('reactivar')
                    ->label('Reactivar')
                    ->icon(Heroicon::CheckCircle)
                    ->color('success')
                    ->visible(fn (Customer $record): bool => ! $record->is_active && ! $record->trashed())
                    ->requiresConfirmation()
                    ->modalHeading('Reactivar cliente')
                    ->action(fn (Customer $record) => $record->update(['is_active' => true])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('suspender')
                        ->label('Suspender')
                        ->icon(Heroicon::NoSymbol)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('Los clientes seleccionados no van a poder iniciar sesión. Se conservan sus datos y órdenes.')
                        ->action(fn (Collection $records) => $records->each->suspend())
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('reactivar')
                        ->label('Reactivar')
                        ->icon(Heroicon::CheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    // Se conserva para rescatar los clientes borrados antes de
                    // que se quitara la opción de eliminar (filtro "Eliminados").
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
