<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Models\Quote;
use App\Models\ShippingMethod;
use App\Services\Shipping\AndreaniProvider;
use App\Services\Shipping\ShippingRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function rates(Request $request): JsonResponse
    {
        $customer = $request->user();
        $quote    = Quote::getActiveForCustomer($customer);
        $quote->load('items');

        if ($quote->items->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $rates = [];

        if ($pickup = ShippingMethod::where('code', 'retiro_sucursal')->where('is_active', true)->first()) {
            $rates[] = $this->pickupRate($pickup);
        }

        /* Andreani requiere una dirección para cotizar; el retiro no. */
        if ($quote->checkout_address_id) {
            $address = CustomerAddress::findOrFail($quote->checkout_address_id);

            $weightGrams   = $this->estimateWeight($quote);
            $declaredValue = (float) $quote->items->sum(fn ($i) => $i->unit_price * $i->quantity);

            if ($method = ShippingMethod::where('code', 'andreani')->where('is_active', true)->first()) {
                $provider = new AndreaniProvider($method);
                $rates    = array_merge($rates, $provider->getRates($address->zip_code, $weightGrams, $declaredValue));
            }
        }

        return response()->json([
            'data' => array_map(fn ($r) => $r->toArray(), $rates),
        ]);
    }

    public function select(Request $request): JsonResponse
    {
        $request->validate([
            'code'  => ['required', 'string'],
            'label' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $customer = $request->user();
        $quote    = Quote::getActiveForCustomer($customer);
        $quote->load('items');

        if ($request->code === 'retiro_sucursal') {
            $pickup = ShippingMethod::where('code', 'retiro_sucursal')->where('is_active', true)->first();

            if (! $pickup) {
                return response()->json(['message' => 'El retiro en sucursal no está disponible.'], 422);
            }

            $quote->update([
                'shipping_method_code'  => 'retiro_sucursal',
                'shipping_method_label' => $pickup->name,
                'shipping_cost'         => 0,
                'checkout_address_id'   => null,
            ]);

            return response()->json(['message' => 'Método de envío seleccionado.']);
        }

        if (! $quote->checkout_address_id) {
            return response()->json(['message' => 'Seleccioná una dirección primero.'], 422);
        }

        $quote->update([
            'shipping_method_code'  => $request->code,
            'shipping_method_label' => $request->label,
            'shipping_cost'         => $request->price,
        ]);

        return response()->json(['message' => 'Método de envío seleccionado.']);
    }

    private function pickupRate(ShippingMethod $method): ShippingRate
    {
        return new ShippingRate(
            code: 'retiro_sucursal',
            label: $method->name,
            price: 0,
            estimatedDays: 'Retirá en el local',
            isPickup: true,
            pickupAddress: $method->configValue('pickup_address'),
            pickupSchedule: $method->configValue('pickup_schedule'),
        );
    }

    private function estimateWeight(Quote $quote): int
    {
        /* 500g per item unit as default estimate until products have weight field */
        return (int) $quote->items->sum(fn ($item) => $item->quantity * 500);
    }
}
