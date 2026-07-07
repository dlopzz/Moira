<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderReturnController extends Controller
{
    public function eligibleItems(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('view-order', $order);
        OrderReturn::assertOrderIsReturnable($order);

        $returnedByItem = OrderItem::returnedQuantities($order->items->pluck('id'));

        $items = $order->items->map(fn ($item) => [
            'order_item_id' => $item->id,
            'product_name' => $item->product_name,
            'variant_label' => $item->variant_label,
            'quantity' => $item->quantity,
            'remaining_quantity' => max(0, $item->quantity - (int) ($returnedByItem[$item->id] ?? 0)),
        ])->filter(fn (array $row) => $row['remaining_quantity'] > 0)->values();

        return response()->json([
            'data' => $items,
            'reasons' => OrderReturn::REASONS,
        ]);
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('view-order', $order);

        $data = $request->validate([
            'reason' => ['required', 'string', 'in:'.implode(',', OrderReturn::REASONS)],
            'description' => ['nullable', 'string'],
            'telephone' => ['required', 'string', 'max:30'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $orderReturn = OrderReturn::createWithItems(
            order: $order,
            rows: $data['items'],
            reason: $data['reason'],
            description: $data['description'] ?? null,
            telephone: $data['telephone'],
        );

        return response()->json([
            'message' => 'Solicitud recibida. Moira se va a poner en contacto con vos.',
            'data' => ['id' => $orderReturn->id],
        ], 201);
    }
}
