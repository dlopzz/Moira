<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddCartItemRequest;
use App\Http\Requests\Api\ApplyCouponRequest;
use App\Http\Requests\Api\UpdateCartItemRequest;
use App\Http\Resources\Api\CartResource;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $quote = Quote::getActiveForCustomer($request->user());
        $quote->load('items');

        return response()->json(['data' => new CartResource($quote)]);
    }

    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $product = Product::findOrFail($request->product_id);
        $variantId = null;
        $variantLabel = null;

        if ($product->isConfigurable()) {
            if (! $request->filled('variant_id')) {
                return response()->json(['message' => 'Seleccioná una variante antes de agregar al carrito.'], 422);
            }

            $variant = ProductVariant::where('id', $request->variant_id)
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->firstOrFail();

            if ($variant->stock < $request->quantity) {
                return response()->json(['message' => "Stock insuficiente. Disponible: {$variant->stock}."], 422);
            }

            $variant->loadMissing('product');
            $price = $variant->effectivePrice();
            $variantId = $variant->id;
            $variantLabel = $variant->label();
        } else {
            if ($product->stock < $request->quantity) {
                return response()->json(['message' => "Stock insuficiente. Disponible: {$product->stock}."], 422);
            }

            $price = (float) ($product->sale_price ?? $product->price);
        }

        $quote = Quote::getActiveForCustomer($request->user());

        $item = $quote->items()
            ->where('product_id', $product->id)
            ->where('variant_id', $variantId)
            ->first();

        if ($item) {
            $newQty = $item->quantity + $request->quantity;
            $item->update([
                'quantity' => $newQty,
                'subtotal' => $price * $newQty,
            ]);
        } else {
            $quote->items()->create([
                'product_id'    => $product->id,
                'variant_id'    => $variantId,
                'variant_label' => $variantLabel,
                'product_name'  => $product->name,
                'product_sku'   => $variantId ? ($variant->sku ?? $product->sku) : $product->sku,
                'product_image' => $product->images[0] ?? null,
                'unit_price'    => $price,
                'quantity'      => $request->quantity,
                'subtotal'      => $price * $request->quantity,
            ]);
        }

        $quote->refreshExpiry();

        if ($quote->coupon_id) {
            $quote->load('items');
            $quote->recalculateDiscount();
        }

        $quote->load('items');

        return response()->json(['data' => new CartResource($quote)], 201);
    }

    public function updateItem(UpdateCartItemRequest $request, QuoteItem $item): JsonResponse
    {
        abort_if($item->quote->customer_id !== $request->user()->id, 403);

        $price = (float) $item->unit_price;
        $newQty = $request->quantity;

        $item->update([
            'quantity' => $newQty,
            'subtotal' => $price * $newQty,
        ]);

        $quote = $item->quote;
        $quote->refreshExpiry();

        if ($quote->coupon_id) {
            $quote->load('items');
            $quote->recalculateDiscount();
        }

        $quote->load('items');

        return response()->json(['data' => new CartResource($quote)]);
    }

    public function removeItem(Request $request, QuoteItem $item): JsonResponse
    {
        $quote = $item->quote;
        abort_if($quote->customer_id !== $request->user()->id, 403);

        $item->delete();

        $quote->refreshExpiry();

        if ($quote->coupon_id) {
            $quote->load('items');
            $quote->recalculateDiscount();
        }

        $quote->load('items');

        return response()->json(['data' => new CartResource($quote)]);
    }

    public function applyCoupon(ApplyCouponRequest $request): JsonResponse
    {
        $quote = Quote::getActiveForCustomer($request->user());
        $quote->load('items');

        $coupon = Coupon::where('code', strtoupper($request->code))
            ->where('is_active', true)
            ->first();

        if (! $coupon) {
            return response()->json(['message' => 'El cupón no es válido.'], 422);
        }

        if ($coupon->isExpired()) {
            return response()->json(['message' => 'El cupón ha expirado.'], 422);
        }

        if ($coupon->hasReachedMaxUses()) {
            return response()->json(['message' => 'El cupón ha alcanzado el límite de usos.'], 422);
        }

        $subtotal = $quote->getSubtotal();

        if ($coupon->min_order_amount && $subtotal < (float) $coupon->min_order_amount) {
            return response()->json([
                'message' => 'El pedido mínimo para este cupón es $'.number_format((float) $coupon->min_order_amount, 2),
            ], 422);
        }

        $discount = $coupon->type === 'percentage'
            ? $subtotal * ((float) $coupon->value / 100)
            : min((float) $coupon->value, $subtotal);

        $quote->update([
            'coupon_id'       => $coupon->id,
            'coupon_code'     => $coupon->code,
            'discount_amount' => round($discount, 2),
        ]);

        $quote->refresh();
        $quote->load('items');

        return response()->json(['data' => new CartResource($quote)]);
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $quote = Quote::getActiveForCustomer($request->user());

        $quote->update([
            'coupon_id'       => null,
            'coupon_code'     => null,
            'discount_amount' => 0,
        ]);

        $quote->load('items');

        return response()->json(['data' => new CartResource($quote)]);
    }
}
