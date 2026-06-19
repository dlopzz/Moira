<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    public function store(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password): void {
                $customer->forceFill(['password' => $password])->save();
                $customer->tokens()->delete();
            }
        );

        if ($status !== Password::PasswordReset) {
            return response()->json([
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'message' => 'Contraseña restablecida correctamente.',
        ]);
    }
}
