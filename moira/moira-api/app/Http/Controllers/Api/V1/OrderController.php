<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('customer_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'data' => OrderResource::collection($orders),
            'meta' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('view-order', $order);

        $order->load('items');

        return response()->json(['data' => new OrderResource($order)]);
    }
}
