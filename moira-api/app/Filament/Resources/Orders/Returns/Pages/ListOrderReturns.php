<?php

namespace App\Filament\Resources\Orders\Returns\Pages;

use App\Filament\Resources\Orders\Returns\OrderReturnResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrderReturns extends ListRecords
{
    protected static string $resource = OrderReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
