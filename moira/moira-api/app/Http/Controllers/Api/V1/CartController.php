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
    private function resolveQuote(Request $request): Quote
    {
        // Cart routes are outside auth:sanctum middleware, so try both guards.
        $customer   = $request->user() ?? \Illuminate\Support\Facades\Auth::guard('sanctum')->user();
        $guestToken = $request->header('X-Guest-Token', '');

        if ($customer) {
            $quote = Quote::getActiveForCustomer($customer);

            // Merge guest cart items if the request carries a guest token
            if ($guestToken && preg_match('/^[0-9a-f\-]{36}$/i', $guestToken)) {
                $this->mergeGuestCart($quote, $guestToken);
            }

            return $quote;
        }

        abort_if(! $guestToken || ! preg_match('/^[0-9a-f\-]{36}$/i', $guestToken), 400, 'Token de invitado requerido.');

        return Quote::getActiveForGuest($guestToken);
    }

    private function mergeGuestCart(Quote $customerQuote, string $guestToken): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($customerQuote, $guestToken): void {
            $guestQuote = Quote::where('guest_token', $guestToken)
                ->where('status', Quote::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if (! $guestQuote) {
                return;
            }

            $guestQuote->load('items');

            if ($guestQuote->items->isEmpty()) {
                return;
            }

            foreach ($guestQuote->items as $guestItem) {
                $existing = $customerQuote->items()
                    ->where('product_id', $guestItem->product_id)
                    ->where('variant_id', $guestItem->variant_id)
                    ->first();

                if ($existing) {
                    $newQty = $existing->quantity + $guestItem->quantity;
                    $existing->update([
                        'quantity' => $newQty,
                        'subtotal' => $existing->unit_price * $newQty,
                    ]);
                } else {
                    $customerQuote->items()->create($guestItem->only([
                        'product_id', 'product_slug', 'variant_id', 'variant_label',
                        'product_name', 'product_sku', 'product_image',
                        'unit_price', 'quantity', 'subtotal',
                    ]));
                }
            }

            $guestQuote->update(['status' => Quote::STATUS_CONVERTED]);
        });
    }

    public function index(Request $request): JsonResponse
    {
        $quote = $this->resolveQuote($request);
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

        $quote = $this->resolveQuote($request);

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
                'product_slug'  => $product->slug,
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
        $quote = $this->resolveQuote($request);
        abort_if($item->quote_id !== $quote->id, 403);

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
        $quote = $this->resolveQuote($request);
        abort_if($item->quote_id !== $quote->id, 403);
        $quote = $item->quote;

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
        $quote = $this->resolveQuote($request);
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
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'discount_amount' => round($discount, 2),
        ]);

        $quote->refresh();
        $quote->load('items');

        return response()->json(['data' => new CartResource($quote)]);
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $quote = $this->resolveQuote($request);

        $quote->update([
            'coupon_id' => null,
            'coupon_code' => null,
            'discount_amount' => 0,
        ]);

        $quote->load('items');

        return response()->json(['data' => new CartResource($quote)]);
    }
}
