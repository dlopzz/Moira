<?php

namespace App\Filament\Resources\Marketing\ContactMessages\Pages;

use App\Filament\Resources\Marketing\ContactMessages\ContactMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
