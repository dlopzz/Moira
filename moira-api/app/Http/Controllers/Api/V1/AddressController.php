<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAddressRequest;
use App\Http\Requests\Api\UpdateAddressRequest;
use App\Http\Resources\Api\AddressResource;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $addresses = $customer->addresses()->get()->each->setRelation('customer', $customer);

        return response()->json([
            'data' => AddressResource::collection($addresses),
        ]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $validated = $request->validated();
        $address = $customer->addresses()->create($validated);

        $updates = [];
        if (!empty($validated['is_default_billing'])) {
            $updates['default_billing_address_id'] = $address->id;
        }
        if (!empty($validated['is_default_shipping'])) {
            $updates['default_shipping_address_id'] = $address->id;
        }
        if ($updates) {
            $customer->update($updates);
        }

        $address->setRelation('customer', $customer->fresh());

        return response()->json([
            'data' => new AddressResource($address),
        ], 201);
    }

    public function update(UpdateAddressRequest $request, CustomerAddress $address): JsonResponse
    {
        $this->authorizeAddress($request, $address);

        /** @var Customer $customer */
        $customer = $request->user();

        $validated = $request->validated();
        $address->update($validated);

        $updates = [];
        if (!empty($validated['is_default_billing'])) {
            $updates['default_billing_address_id'] = $address->id;
        }
        if (!empty($validated['is_default_shipping'])) {
            $updates['default_shipping_address_id'] = $address->id;
        }
        if ($updates) {
            $customer->update($updates);
        }

        $address->setRelation('customer', $customer->fresh());

        return response()->json([
            'data' => new AddressResource($address),
        ]);
    }

    public function destroy(Request $request, CustomerAddress $address): Response
    {
        $this->authorizeAddress($request, $address);

        /** @var Customer $customer */
        $customer = $request->user();

        Quote::where('checkout_address_id', $address->id)
            ->where('status', Quote::STATUS_ACTIVE)
            ->update(['checkout_address_id' => null]);

        $updates = [];
        if ($customer->default_billing_address_id === $address->id) {
            $updates['default_billing_address_id'] = null;
        }
        if ($customer->default_shipping_address_id === $address->id) {
            $updates['default_shipping_address_id'] = null;
        }
        if ($updates) {
            $customer->update($updates);
        }

        $address->delete();

        return response()->noContent();
    }

    public function setDefault(Request $request, CustomerAddress $address, string $type): JsonResponse
    {
        $this->authorizeAddress($request, $address);

        abort_unless(in_array($type, ['billing', 'shipping'], true), 422, 'Invalid type');

        /** @var Customer $customer */
        $customer = $request->user();

        $field = $type === 'billing' ? 'default_billing_address_id' : 'default_shipping_address_id';
        $customer->update([$field => $address->id]);

        $address->setRelation('customer', $customer->fresh());

        return response()->json([
            'data' => new AddressResource($address),
        ]);
    }

    private function authorizeAddress(Request $request, CustomerAddress $address): void
    {
        abort_if($address->customer_id !== $request->user()->id, 403);
    }
}
