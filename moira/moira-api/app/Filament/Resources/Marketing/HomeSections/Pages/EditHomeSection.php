<?php

namespace App\Filament\Resources\Marketing\HomeSections\Pages;

use App\Filament\Resources\Marketing\HomeSections\HomeSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHomeSection extends EditRecord
{
    protected static string $resource = HomeSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return HomeSectionResource::unpackSettings($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return HomeSectionResource::packSettings($data);
    }
}
