<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected string $view = 'filament.resources.categories.pages.edit-category';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deleteImage')
                ->label('Eliminar imagen')
                ->icon('heroicon-o-photo')
                ->color('danger')
                ->outlined()
                ->requiresConfirmation()
                ->modalHeading('¿Eliminar imagen?')
                ->modalDescription('Se eliminará la imagen de esta categoría. Esta acción no se puede deshacer.')
                ->modalSubmitActionLabel('Sí, eliminar')
                ->visible(fn () => filled($this->record->image))
                ->action(function () {
                    Storage::disk('public')->delete($this->record->image);
                    $this->record->update(['image' => null]);
                    $this->refreshFormData(['image']);
                    Notification::make()
                        ->title('Imagen eliminada')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
