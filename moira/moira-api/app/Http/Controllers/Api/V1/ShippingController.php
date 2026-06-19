<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Models\Quote;
use App\Models\ShippingMethod;
use App\Services\Shipping\AndreaniProvider;
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

        if (! $quote->checkout_address_id) {
            return response()->json(['message' => 'Seleccioná una dirección primero.'], 422);
        }

        $address = CustomerAddress::findOrFail($quote->checkout_address_id);

        $weightGrams    = $this->estimateWeight($quote);
        $declaredValue  = (float) $quote->items->sum(fn ($i) => $i->unit_price * $i->quantity);

        $method = ShippingMethod::where('code', 'andreani')->where('is_active', true)->first();

        if (! $method) {
            return response()->json(['data' => []]);
        }

        $provider = new AndreaniProvider($method);
        $rates    = $provider->getRates($address->zip_code, $weightGrams, $declaredValue);

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

    private function estimateWeight(Quote $quote): int
    {
        /* 500g per item unit as default estimate until products have weight field */
        return (int) $quote->items->sum(fn ($item) => $item->quantity * 500);
    }
}
