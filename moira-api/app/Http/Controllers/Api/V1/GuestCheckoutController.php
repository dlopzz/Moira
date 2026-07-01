<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrderResource;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quote;
use App\Models\ShippingMethod;
use App\Services\Payment\PayWayProvider;
use App\Services\Shipping\AndreaniProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class GuestCheckoutController extends Controller
{
    private function resolveGuestQuote(Request $request): Quote
    {
        $guestToken = $request->header('X-Guest-Token', '');
        abort_if(
            ! $guestToken || ! preg_match('/^[0-9a-f\-]{36}$/i', $guestToken),
            400,
            'Token de invitado requerido.'
        );

        return Quote::getActiveForGuest($guestToken);
    }

    public function saveAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'       => ['required', 'email', 'max:255'],
            'firstname'   => ['required', 'string', 'max:100'],
            'lastname'    => ['required', 'string', 'max:100'],
            'telephone'   => ['required', 'string', 'max:30'],
            'street'      => ['required', 'string', 'max:255'],
            'city'        => ['required', 'string', 'max:100'],
            'state'       => ['required', 'string', 'max:100'],
            'zip_code'    => ['required', 'string', 'max:20'],
            'country'     => ['nullable', 'string', 'max:2'],
            'order_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $quote = $this->resolveGuestQuote($request);
        $quote->update([
            'guest_email'          => $validated['email'],
            'shipping_firstname'   => $validated['firstname'],
            'shipping_lastname'    => $validated['lastname'],
            'shipping_telephone'   => $validated['telephone'],
            'shipping_street'      => $validated['street'],
            'shipping_city'        => $validated['city'],
            'shipping_state'       => $validated['state'],
            'shipping_zip_code'    => $validated['zip_code'],
            'shipping_country'     => $validated['country'] ?? 'AR',
            'order_notes'          => $validated['order_notes'] ?? null,
        ]);

        return response()->json(['message' => 'Dirección guardada.']);
    }

    public function saveNotes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $quote = $this->resolveGuestQuote($request);
        $quote->update(['order_notes' => $validated['order_notes'] ?? null]);

        return response()->json(['message' => 'Notas guardadas.']);
    }

    public function shippingRates(Request $request): JsonResponse
    {
        $quote = $this->resolveGuestQuote($request);
        $quote->load('items');

        if ($quote->items->isEmpty()) {
            return response()->json(['data' => []]);
        }

        if (! $quote->shipping_zip_code) {
            return response()->json(['message' => 'Ingresá una dirección de envío primero.'], 422);
        }

        $weightGrams   = (int) $quote->items->sum(fn ($i) => $i->quantity * 500);
        $declaredValue = (float) $quote->items->sum(fn ($i) => $i->unit_price * $i->quantity);

        $method = ShippingMethod::where('code', 'andreani')->where('is_active', true)->first();

        if (! $method) {
            return response()->json(['data' => []]);
        }

        $provider = new AndreaniProvider($method);
        $rates    = $provider->getRates($quote->shipping_zip_code, $weightGrams, $declaredValue);

        return response()->json([
            'data' => array_map(fn ($r) => $r->toArray(), $rates),
        ]);
    }

    public function selectShipping(Request $request): JsonResponse
    {
        $request->validate([
            'code'  => ['required', 'string'],
            'label' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $quote = $this->resolveGuestQuote($request);

        if (! $quote->shipping_zip_code) {
            return response()->json(['message' => 'Ingresá una dirección de envío primero.'], 422);
        }

        $quote->update([
            'shipping_method_code'  => $request->code,
            'shipping_method_label' => $request->label,
            'shipping_cost'         => $request->price,
        ]);

        return response()->json(['message' => 'Método de envío seleccionado.']);
    }

    public function pay(Request $request): JsonResponse
    {
        $request->validate([
            'token'                  => ['required', 'string'],
            'bin'                    => ['required', 'string'],
            'payment_method_id'      => ['required', 'integer'],
            'installments'           => ['required', 'integer', 'min:1', 'max:36'],
            'card_holder_name'       => ['required', 'string'],
            'card_holder_doc_type'     => ['required', 'string', 'in:dni,le,lc,ci,pasaporte'],
            'card_holder_doc_number'   => ['required', 'string'],
            'device_unique_identifier' => ['nullable', 'string'],
        ]);

        $quote = $this->resolveGuestQuote($request);
        $quote->load('items.product', 'items.variant');

        if ($quote->items->isEmpty()) {
            return response()->json(['message' => 'El carrito está vacío.'], 422);
        }

        if (! $quote->shipping_zip_code) {
            return response()->json(['message' => 'Ingresá una dirección de envío.'], 422);
        }

        if (! $quote->shipping_method_code) {
            return response()->json(['message' => 'Seleccioná un método de envío.'], 422);
        }

        if (! $quote->guest_email) {
            return response()->json(['message' => 'Ingresá tu email en la dirección de envío para continuar.'], 422);
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

        $reserved = Quote::where('id', $quote->id)
            ->where('status', Quote::STATUS_ACTIVE)
            ->update(['status' => Quote::STATUS_PROCESSING]);

        if (! $reserved) {
            return response()->json(['message' => 'Tu pago ya está siendo procesado. Si el problema persiste, intentá nuevamente en unos minutos.'], 422);
        }

        $subtotal     = $quote->getSubtotal();
        $shippingCost = (float) $quote->shipping_cost;
        $discount     = (float) $quote->discount_amount;
        $total        = $quote->getTotal();
        $amountCents  = (int) round($total * 100);

        $provider = new PayWayProvider($method);
        $result   = $provider->charge(
            customerEmail: $quote->guest_email,
            amountCents: $amountCents,
            tokenData: $request->only([
                'token', 'bin', 'payment_method_id',
                'installments', 'card_holder_name',
                'card_holder_doc_type', 'card_holder_doc_number',
                'device_unique_identifier',
            ]),
        );

        if (! $result->approved && ! $result->pending) {
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

        try {
            $order = DB::transaction(function () use (
                $quote, $subtotal, $shippingCost, $discount, $total,
                $result, $method, $request, $orderStatus, $amountCents
            ): Order {
                $locked = Quote::lockForUpdate()->find($quote->id);
                if (! $locked || $locked->status !== Quote::STATUS_PROCESSING) {
                    throw new \RuntimeException('Este pedido ya fue procesado.');
                }

                $locked->load('items.product', 'items.variant');

                $order = Order::create([
                    'customer_id'           => null,
                    'payment_method_id'     => $method->id,
                    'status'                => $orderStatus,
                    'shipping_address'      => [
                        'label'          => trim($locked->shipping_firstname . ' ' . $locked->shipping_lastname),
                        'street'         => $locked->shipping_street,
                        'address_line_2' => null,
                        'city'           => $locked->shipping_city,
                        'state'          => $locked->shipping_state,
                        'zip_code'       => $locked->shipping_zip_code,
                        'country'        => $locked->shipping_country,
                        'telephone'      => $locked->shipping_telephone,
                    ],
                    'subtotal'              => $subtotal,
                    'shipping_cost'         => $shippingCost,
                    'shipping_method_label' => $locked->shipping_method_label,
                    'discount'              => $discount,
                    'coupon_code'           => $locked->coupon_code,
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

                foreach ($locked->items as $item) {
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

                $locked->update(['status' => Quote::STATUS_CONVERTED]);

                return $order;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            \Log::critical('PayWay orphaned charge (guest) — transaction failed after successful charge', [
                'payway_transaction_id' => $result->transactionId,
                'amount_cents'          => $amountCents,
                'guest_token'           => '…' . substr((string) $request->header('X-Guest-Token'), -6),
                'error'                 => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Tu pago fue procesado pero ocurrió un error al registrar el pedido. Contactá al soporte con el código de referencia: ' . $result->transactionId,
            ], 500);
        }

        $order->load('items');

        if ($result->approved && $quote->guest_email) {
            Mail::to($quote->guest_email)->queue(new OrderConfirmationMail($order));
        }

        return response()->json(['data' => new OrderResource($order)], 201);
    }

    public function simulatePayment(Request $request): JsonResponse
    {
        abort_unless(! app()->isProduction(), 404);

        $request->validate(['result' => ['required', 'in:success,fail']]);

        if ($request->result === 'fail') {
            return response()->json(['message' => 'Pago rechazado (simulación).'], 422);
        }

        $quote = $this->resolveGuestQuote($request);
        $quote->load('items.product', 'items.variant');

        if ($quote->items->isEmpty()) {
            return response()->json(['message' => 'El carrito está vacío.'], 422);
        }

        if (! $quote->shipping_zip_code) {
            return response()->json(['message' => 'Ingresá una dirección de envío.'], 422);
        }

        if (! $quote->shipping_method_code) {
            return response()->json(['message' => 'Seleccioná un método de envío.'], 422);
        }

        $subtotal     = $quote->getSubtotal();
        $shippingCost = (float) $quote->shipping_cost;
        $discount     = (float) $quote->discount_amount;
        $total        = $quote->getTotal();

        try {
            $order = DB::transaction(function () use ($quote, $subtotal, $shippingCost, $discount, $total): Order {
                $locked = Quote::lockForUpdate()->find($quote->id);
                if (! $locked || $locked->status !== Quote::STATUS_ACTIVE) {
                    throw new \RuntimeException('Este pedido ya fue procesado.');
                }

                // Re-fetch items inside the transaction from the locked row
                $locked->load('items.product', 'items.variant');

                $order = Order::create([
                    'customer_id'           => null,
                    'status'                => 'paid',
                    'shipping_address'      => [
                        'label'          => trim($locked->shipping_firstname . ' ' . $locked->shipping_lastname),
                        'street'         => $locked->shipping_street,
                        'address_line_2' => null,
                        'city'           => $locked->shipping_city,
                        'state'          => $locked->shipping_state,
                        'zip_code'       => $locked->shipping_zip_code,
                        'country'        => $locked->shipping_country,
                        'telephone'      => $locked->shipping_telephone,
                    ],
                    'subtotal'              => $subtotal,
                    'shipping_cost'         => $shippingCost,
                    'shipping_method_label' => $locked->shipping_method_label,
                    'discount'              => $discount,
                    'coupon_code'           => $locked->coupon_code,
                    'total'                 => $total,
                    'notes'                 => json_encode(['simulated' => true, 'guest_email' => $locked->guest_email]),
                ]);

                foreach ($locked->items as $item) {
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

                $locked->update(['status' => Quote::STATUS_CONVERTED]);

                return $order;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $order->load('items');

        if ($quote->guest_email) {
            Mail::to($quote->guest_email)->queue(new OrderConfirmationMail($order));
        }

        return response()->json(['data' => new OrderResource($order)], 201);
    }

    public function show(Request $request): JsonResponse
    {
        $quote = $this->resolveGuestQuote($request);

        return response()->json([
            'shipping_address' => $quote->shipping_zip_code ? [
                'email'     => $quote->guest_email,
                'firstname' => $quote->shipping_firstname,
                'lastname'  => $quote->shipping_lastname,
                'telephone' => $quote->shipping_telephone,
                'street'    => $quote->shipping_street,
                'city'      => $quote->shipping_city,
                'state'     => $quote->shipping_state,
                'zip_code'  => $quote->shipping_zip_code,
                'country'   => $quote->shipping_country,
            ] : null,
            'shipping_method' => $quote->shipping_method_code ? [
                'code'           => $quote->shipping_method_code,
                'label'          => $quote->shipping_method_label,
                'price'          => (float) $quote->shipping_cost,
                'estimated_days' => null,
            ] : null,
            'order_notes' => $quote->order_notes,
        ]);
    }
}
