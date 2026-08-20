<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\Api\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new CustomerResource($request->user()),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        // validated() ya excluye el email (ver UpdateProfileRequest). El except
        // es un segundo cerrojo: si alguien vuelve a agregar la regla sin pensar
        // en email_verified_at, el email igual no se toca desde acá.
        $customer->update($request->safe()->except('email'));

        return response()->json([
            'data' => new CustomerResource($customer->fresh()),
        ]);
    }
}
