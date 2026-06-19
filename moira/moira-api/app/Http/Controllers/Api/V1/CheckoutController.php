<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CartResource;
use App\Http\Resources\Api\OrderResource;
use App\Mail\OrderConfirmationMail;
use App\Mail\ReviewRequestMail;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quote;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $quote = Quote::getActiveForCustomer($request->user());
        $quote->load('items');

        $address = $quote->checkout_address_id
            ? CustomerAddress::find($quote->checkout_address_id)
            : null;

        return response()->json([
            'cart' => new CartResource($quote),
            'checkout_address' => $address,
        ]);
    }

    public function setAddress(Request $request): JsonResponse
    {
        $request->validate([
            'address_id' => ['required', 'integer'],
        ]);

        $address = CustomerAddress::where('id', $request->address_id)
            ->where('customer_id', $request->user()->id)
            ->firstOrFail();

        $quote = Quote::getActiveForCustomer($request->user());
        $quote->update(['checkout_address_id' => $address->id]);

        return response()->json(['message' => 'Dirección guardada.']);
    }

    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'simulate' => ['required', 'in:success,fail'],
        ]);

        if ($request->simulate === 'fail') {
            return response()->json(['message' => 'El pago fue rechazado. Intentá nuevamente.'], 422);
        }

        $customer = $request->user();
        $quote = Quote::getActiveForCustomer($customer);
        $quote->load('items');

        if ($quote->items->isEmpty()) {
            return response()->json(['message' => 'El carrito está vacío.'], 422);
        }

        if (! $quote->checkout_address_id) {
            return response()->json(['message' => 'Seleccioná una dirección de envío.'], 422);
        }

        if (! $quote->shipping_method_code) {
            return response()->json(['message' => 'Seleccioná un método de envío.'], 422);
        }

        $address = CustomerAddress::findOrFail($quote->checkout_address_id);

        $quote->load('items.product', 'items.variant');
        foreach ($quote->items as $item) {
            if (! $item->product) {
                return response()->json(['message' => "El producto '{$item->product_name}' ya no está disponible."], 422);
            }

            $available = $item->variant_id ? $item->variant?->stock : $item->product->stock;

            if ($available < $item->quantity) {
                return response()->json([
                    'message' => "Stock insuficiente para '{$item->product_name}'" .
                        ($item->variant_label ? " ({$item->variant_label})" : '') .
                        ". Disponible: {$available}.",
                ], 422);
            }
        }

        $subtotal     = $quote->getSubtotal();
        $shippingCost = (float) $quote->shipping_cost;
        $discount     = (float) $quote->discount_amount;
        $total        = $quote->getTotal();

        $order = DB::transaction(function () use ($customer, $quote, $address, $subtotal, $shippingCost, $discount, $total): Order {
            $order = Order::create([
                'customer_id'     => $customer->id,
                'status'          => 'pending',
                'shipping_address' => [
                    'label'          => $address->label,
                    'street'         => $address->street,
                    'address_line_2' => $address->address_line_2,
                    'city'           => $address->city,
                    'state'          => $address->state,
                    'zip_code'       => $address->zip_code,
                    'country'        => $address->country,
                    'telephone'      => $address->telephone,
                ],
                'subtotal'              => $subtotal,
                'shipping_cost'         => $shippingCost,
                'shipping_method_label' => $quote->shipping_method_label,
                'discount'              => $discount,
                'total'                 => $total,
            ]);

            foreach ($quote->items as $item) {
                $order->items()->create([
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product_name,
                    'unit_price'   => $item->unit_price,
                    'quantity'     => $item->quantity,
                    'subtotal'     => $item->subtotal,
                ]);

                /* Decrement stock atomically — variant stock for configurables, product stock for simples */
                if ($item->variant_id) {
                    ProductVariant::where('id', $item->variant_id)
                        ->decrement('stock', $item->quantity);
                } elseif ($item->product_id) {
                    Product::where('id', $item->product_id)
                        ->decrement('stock', $item->quantity);
                }
            }

            $quote->update(['status' => Quote::STATUS_CONVERTED]);

            return $order;
        });

        $order->load('items', 'customer');
        $order->setRelation('customer', $customer);
        Mail::to($customer->email)->queue(new OrderConfirmationMail($order));

        $reviews = collect();
        foreach ($order->items as $item) {
            $product = $quote->items->firstWhere('product_id', $item->product_id)?->product;
            if (! $item->product_id || ! $product) {
                continue;
            }
            $review = Review::create([
                'product_id'  => $item->product_id,
                'customer_id' => $customer->id,
                'order_id'    => $order->id,
                'token'       => Str::uuid()->toString(),
            ]);
            $review->setRelation('product', $product);
            $reviews->push($review);
        }

        if ($reviews->isNotEmpty()) {
            Mail::to($customer->email)->queue(new ReviewRequestMail($order, $reviews));
        }

        return response()->json(['data' => new OrderResource($order)], 201);
    }
}
