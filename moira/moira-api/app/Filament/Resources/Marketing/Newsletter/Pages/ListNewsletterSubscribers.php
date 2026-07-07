<?php

namespace App\Filament\Resources\Marketing\Newsletter\Pages;

use App\Filament\Resources\Marketing\Newsletter\NewsletterSubscriberResource;
use Filament\Resources\Pages\ListRecords;

class ListNewsletterSubscribers extends ListRecords
{
    protected static string $resource = NewsletterSubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
