<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrderResource;
use App\Mail\OrderConfirmationMail;
use App\Mail\ReviewRequestMail;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quote;
use App\Models\Review;
use App\Services\Payment\PayWayProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Public endpoint — returns the PayWay public key and environment so the
     * frontend JS SDK can initialise without exposing the private key.
     */
    public function config(): JsonResponse
    {
        $method = PaymentMethod::where('code', 'payway')->where('is_active', true)->first();

        if (! $method) {
            return response()->json(['data' => null]);
        }

        $provider = new PayWayProvider($method);

        return response()->json([
            'data' => [
                'public_key'   => $method->activePublicKey(),
                'is_sandbox'   => $method->is_sandbox,
                'sdk_endpoint' => $provider->apiBaseUrl() . '/api/v2',
                'js_sdk_url'   => $provider->jsSdkUrl(),
            ],
        ]);
    }

    /**
     * Process payment. Receives the card token from the PayWay JS SDK,
     * charges via PayWay, creates the order, sends emails.
     */
    public function pay(Request $request): JsonResponse
    {
        $request->validate([
            'token'                  => ['required', 'string'],
            'bin'                    => ['required', 'string'],
            'payment_method_id'      => ['required', 'integer'],
            'installments'           => ['required', 'integer', 'min:1', 'max:36'],
            'card_holder_name'       => ['required', 'string'],
            'card_holder_doc_type'        => ['required', 'string', 'in:dni,le,lc,ci,pasaporte'],
            'card_holder_doc_number'      => ['required', 'string'],
            'device_unique_identifier'    => ['nullable', 'string'],
        ]);

        $customer = $request->user();
        $quote    = Quote::getActiveForCustomer($customer);
        $quote->load('items.product', 'items.variant');

        \Log::debug('[Pay] quote check', [
            'quote_id'            => $quote?->id,
            'quote_status'        => $quote?->status,
            'items_count'         => $quote?->items->count(),
            'checkout_address_id' => $quote?->checkout_address_id,
            'shipping_method'     => $quote?->shipping_method_code,
        ]);

        if ($quote->items->isEmpty()) {
            return response()->json(['message' => 'El carrito está vacío.'], 422);
        }

        if (! $quote->checkout_address_id) {
            return response()->json(['message' => 'Seleccioná una dirección de envío.'], 422);
        }

        if (! $quote->shipping_method_code) {
            return response()->json(['message' => 'Seleccioná un método de envío.'], 422);
        }

        /* Stock pre-check — early return before charging the card */
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

        $method = PaymentMethod::where('code', 'payway')->where('is_active', true)->first();

        if (! $method) {
            return response()->json(['message' => 'El método de pago no está disponible.'], 422);
        }

        // Atomic ACTIVE → PROCESSING transition: prevents concurrent duplicate charges.
        // Only one request can win this UPDATE; the second gets $reserved = 0 and bails out.
        $reserved = Quote::where('id', $quote->id)
            ->where('status', Quote::STATUS_ACTIVE)
            ->update(['status' => Quote::STATUS_PROCESSING]);

        if (! $reserved) {
            return response()->json(['message' => 'Tu pago ya está siendo procesado. Si el problema persiste, intentá nuevamente en unos minutos.'], 422);
        }

        $total       = $quote->getTotal();
        $amountCents = (int) round($total * 100);

        $provider = new PayWayProvider($method);
        $result   = $provider->charge(
            customerEmail: $customer->email,
            amountCents: $amountCents,
            tokenData: $request->only([
                'token', 'bin', 'payment_method_id',
                'installments', 'card_holder_name',
                'card_holder_doc_type', 'card_holder_doc_number',
                'device_unique_identifier',
            ]),
        );

        if (! $result->approved && ! $result->pending) {
            // Charge failed — restore quote to ACTIVE so the customer can retry.
            Quote::where('id', $quote->id)
                ->where('status', Quote::STATUS_PROCESSING)
                ->update(['status' => Quote::STATUS_ACTIVE]);

            $message = match ($result->status) {
                'rejected'  => 'El pago fue rechazado por el banco. Verificá los datos de tu tarjeta.',
                'error'     => 'Ocurrió un error al procesar el pago. Intentá nuevamente.',
                'cancelled' => 'La transacción fue cancelada.',
                default     => 'El pago no pudo procesarse. Intentá nuevamente.',
            };

            return response()->json(['message' => $message, 'status' => $result->status], 422);
        }

        $orderStatus = $result->approved ? 'paid' : 'pending';

        $address      = CustomerAddress::findOrFail($quote->checkout_address_id);
        $subtotal     = $quote->getSubtotal();
        $shippingCost = (float) $quote->shipping_cost;
        $discount     = (float) $quote->discount_amount;

        try {
            $order = DB::transaction(function () use (
                $customer, $quote, $address, $subtotal, $shippingCost, $discount, $total,
                $result, $method, $request, $orderStatus, $amountCents
            ): Order {
                /* Lock the quote row to prevent double-submit races */
                $locked = Quote::lockForUpdate()->find($quote->id);
                if (! $locked || $locked->status !== Quote::STATUS_PROCESSING) {
                    throw new \RuntimeException('Este pedido ya fue procesado.');
                }

                $order = Order::create([
                    'customer_id'           => $customer->id,
                    'payment_method_id'     => $method->id,
                    'status'                => $orderStatus,
                    'shipping_address'      => [
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
                    'coupon_code'           => $quote->coupon_code,
                    'total'                 => $total,
                ]);

                PaymentTransaction::create([
                    'order_id'               => $order->id,
                    'payment_method_id'      => $method->id,
                    'gateway_transaction_id' => $result->transactionId,
                    'site_transaction_id'    => $result->siteTransactionId,
                    'card_authorization_code'=> $result->authCode,
                    'status'                 => $result->status,
                    'amount_cents'           => $amountCents,
                    'installments'           => $request->installments,
                    'bin'                    => $request->bin,
                    'card_brand'             => $result->raw['payment_method_id'] ?? null,
                    'response_raw'           => $result->raw,
                ]);

                foreach ($quote->items as $item) {
                    $order->items()->create([
                        'product_id'    => $item->product_id,
                        'variant_id'    => $item->variant_id,
                        'variant_label' => $item->variant_label,
                        'product_name'  => $item->product_name,
                        'unit_price'    => $item->unit_price,
                        'quantity'      => $item->quantity,
                        'subtotal'      => $item->subtotal,
                    ]);

                    /* Atomic decrement — rejects if stock was depleted by a concurrent request */
                    if ($item->variant_id) {
                        $decremented = ProductVariant::where('id', $item->variant_id)
                            ->where('stock', '>=', $item->quantity)
                            ->decrement('stock', $item->quantity);
                    } else {
                        $decremented = Product::where('id', $item->product_id)
                            ->where('stock', '>=', $item->quantity)
                            ->decrement('stock', $item->quantity);
                    }

                    if (! $decremented) {
                        throw new \RuntimeException("Stock insuficiente para '{$item->product_name}'.");
                    }
                }

                $quote->update(['status' => Quote::STATUS_CONVERTED]);

                return $order;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            \Log::critical('PayWay orphaned charge — transaction failed after successful charge', [
                'payway_transaction_id' => $result->transactionId,
                'amount_cents'          => $amountCents,
                'customer_id'           => $customer->id,
                'error'                 => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Tu pago fue procesado pero ocurrió un error al registrar el pedido. Contactá al soporte con el código de referencia: ' . $result->transactionId,
            ], 500);
        }

        $order->load('items');
        $order->setRelation('customer', $customer);

        if ($result->approved) {
            Mail::to($customer->email)->queue(new OrderConfirmationMail($order));

            $reviews = new \Illuminate\Database\Eloquent\Collection();
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
        }

        return response()->json(['data' => new OrderResource($order)], 201);
    }

    /**
     * Simulator — only available outside production.
     * Used when PayWay credentials are not yet configured.
     */
    public function simulate(Request $request): JsonResponse
    {
        abort_unless(! app()->isProduction(), 404);

        $request->validate([
            'result' => ['required', 'in:success,fail'],
        ]);

        if ($request->result === 'fail') {
            return response()->json(['message' => 'Pago rechazado (simulación).'], 422);
        }

        $customer = $request->user();
        $quote    = Quote::getActiveForCustomer($customer);
        $quote->load('items.product', 'items.variant');

        if ($quote->items->isEmpty()) {
            return response()->json(['message' => 'El carrito está vacío.'], 422);
        }

        if (! $quote->checkout_address_id) {
            return response()->json(['message' => 'Seleccioná una dirección de envío.'], 422);
        }

        if (! $quote->shipping_method_code) {
            return response()->json(['message' => 'Seleccioná un método de envío.'], 422);
        }

        $address      = CustomerAddress::findOrFail($quote->checkout_address_id);
        $subtotal     = $quote->getSubtotal();
        $shippingCost = (float) $quote->shipping_cost;
        $discount     = (float) $quote->discount_amount;
        $total        = $quote->getTotal();

        try {
            $order = DB::transaction(function () use (
                $customer, $quote, $address, $subtotal, $shippingCost, $discount, $total
            ): Order {
                $locked = Quote::lockForUpdate()->find($quote->id);
                if (! $locked || $locked->status !== Quote::STATUS_ACTIVE) {
                    throw new \RuntimeException('Este pedido ya fue procesado.');
                }

                $order = Order::create([
                    'customer_id'           => $customer->id,
                    'status'                => 'paid',
                    'shipping_address'      => [
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
                    'coupon_code'           => $quote->coupon_code,
                    'total'                 => $total,
                    'notes'                 => json_encode(['simulated' => true]),
                ]);

                foreach ($quote->items as $item) {
                    $order->items()->create([
                        'product_id'    => $item->product_id,
                        'variant_id'    => $item->variant_id,
                        'variant_label' => $item->variant_label,
                        'product_name'  => $item->product_name,
                        'unit_price'    => $item->unit_price,
                        'quantity'      => $item->quantity,
                        'subtotal'      => $item->subtotal,
                    ]);

                    if ($item->variant_id) {
                        $decremented = ProductVariant::where('id', $item->variant_id)
                            ->where('stock', '>=', $item->quantity)
                            ->decrement('stock', $item->quantity);
                    } else {
                        $decremented = Product::where('id', $item->product_id)
                            ->where('stock', '>=', $item->quantity)
                            ->decrement('stock', $item->quantity);
                    }

                    if (! $decremented) {
                        throw new \RuntimeException("Stock insuficiente para '{$item->product_name}'.");
                    }
                }

                $quote->update(['status' => Quote::STATUS_CONVERTED]);

                return $order;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $order->load('items');
        $order->setRelation('customer', $customer);
        Mail::to($customer->email)->queue(new OrderConfirmationMail($order));

        $reviews = new \Illuminate\Database\Eloquent\Collection();
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
