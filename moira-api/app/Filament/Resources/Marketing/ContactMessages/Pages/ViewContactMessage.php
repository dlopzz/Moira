<?php

namespace App\Filament\Resources\Marketing\ContactMessages\Pages;

use App\Filament\Resources\Marketing\ContactMessages\ContactMessageResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggle_read')
                ->label(fn () => $this->record->is_read ? 'Marcar como no leído' : 'Marcar como leído')
                ->icon(fn () => $this->record->is_read ? 'heroicon-o-envelope' : 'heroicon-o-envelope-open')
                ->color(fn () => $this->record->is_read ? 'gray' : 'success')
                ->action(function () {
                    $this->record->update(['is_read' => ! $this->record->is_read]);
                    $this->refreshFormData(['is_read']);
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Auto-mark as read when opening the view
        if (! $this->record->is_read) {
            $this->record->update(['is_read' => true]);
        }

        return $data;
    }
}
