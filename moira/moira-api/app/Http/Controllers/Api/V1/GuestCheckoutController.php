<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\ShippingMethod;
use App\Services\Shipping\AndreaniProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
