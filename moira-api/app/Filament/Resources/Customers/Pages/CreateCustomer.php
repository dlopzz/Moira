<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::End;
    }
}
