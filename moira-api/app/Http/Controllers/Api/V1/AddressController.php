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

        return response()->json([
            'data' => AddressResource::collection($customer->addresses()->orderByDesc('is_default')->get()),
        ]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        if ($request->boolean('is_default')) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $address = $customer->addresses()->create($request->validated());

        return response()->json([
            'data' => new AddressResource($address),
        ], 201);
    }

    public function update(UpdateAddressRequest $request, CustomerAddress $address): JsonResponse
    {
        $this->authorizeAddress($request, $address);

        /** @var Customer $customer */
        $customer = $request->user();

        if ($request->boolean('is_default')) {
            $customer->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($request->validated());

        return response()->json([
            'data' => new AddressResource($address->fresh()),
        ]);
    }

    public function destroy(Request $request, CustomerAddress $address): Response
    {
        $this->authorizeAddress($request, $address);

        Quote::where('checkout_address_id', $address->id)
            ->where('status', Quote::STATUS_ACTIVE)
            ->update(['checkout_address_id' => null]);

        $address->delete();

        return response()->noContent();
    }

    public function setDefault(Request $request, CustomerAddress $address): JsonResponse
    {
        $this->authorizeAddress($request, $address);

        /** @var Customer $customer */
        $customer = $request->user();

        $customer->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return response()->json([
            'data' => new AddressResource($address->fresh()),
        ]);
    }

    private function authorizeAddress(Request $request, CustomerAddress $address): void
    {
        abort_if($address->customer_id !== $request->user()->id, 403);
    }
}
