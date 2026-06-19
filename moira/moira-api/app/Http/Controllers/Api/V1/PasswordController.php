<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdatePasswordRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        if (! Hash::check($request->current_password, $customer->password)) {
            return response()->json([
                'message' => 'La contraseña actual es incorrecta.',
                'errors'  => ['current_password' => ['La contraseña actual es incorrecta.']],
            ], 422);
        }

        $customer->update(['password' => $request->password]);

        $currentTokenId = $customer->currentAccessToken()->id;
        $customer->tokens()->where('id', '!=', $currentTokenId)->delete();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }
}
