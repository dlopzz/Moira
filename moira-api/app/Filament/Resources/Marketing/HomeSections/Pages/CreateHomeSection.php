<?php

namespace App\Filament\Resources\Marketing\HomeSections\Pages;

use App\Filament\Resources\Marketing\HomeSections\HomeSectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHomeSection extends CreateRecord
{
    protected static string $resource = HomeSectionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return HomeSectionResource::packSettings($data);
    }
}
