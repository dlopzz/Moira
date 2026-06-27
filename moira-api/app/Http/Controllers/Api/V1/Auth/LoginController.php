<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\Api\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function checkEmail(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $exists = Customer::where('email', $request->email)->exists();
        return response()->json(['exists' => $exists]);
    }

    public function store(LoginRequest $request): JsonResponse
    {
        $customer = Customer::where('email', $request->email)->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            return response()->json([
                'message' => 'Las credenciales son incorrectas.',
            ], 422);
        }

        if (! $customer->is_active) {
            return response()->json([
                'message' => 'Tu cuenta está desactivada.',
            ], 403);
        }

        if (! $customer->hasVerifiedEmail()) {
            return response()->json([
                'message'           => 'Tenés que verificar tu email antes de iniciar sesión.',
                'email_unverified'  => true,
            ], 403);
        }

        $token = $customer->createToken('api')->plainTextToken;

        return response()->json([
            'data'  => new CustomerResource($customer),
            'token' => $token,
        ]);
    }
}
