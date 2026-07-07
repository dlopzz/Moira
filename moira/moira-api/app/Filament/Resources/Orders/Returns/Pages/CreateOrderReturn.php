<?php

namespace App\Filament\Resources\Orders\Returns\Pages;

use App\Filament\Resources\Orders\Returns\OrderReturnResource;
use App\Models\Order;
use App\Models\OrderReturn;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrderReturn extends CreateRecord
{
    protected static string $resource = OrderReturnResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $order = Order::findOrFail($data['order_id']);

        return OrderReturn::createWithItems(
            order: $order,
            rows: $data['items'] ?? [],
            reason: $data['reason'],
            description: $data['description'] ?? null,
            telephone: $data['telephone'],
            restock: (bool) ($data['restock'] ?? true),
        );
    }
}
