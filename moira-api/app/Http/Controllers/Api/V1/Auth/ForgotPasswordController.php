<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function store(ForgotPasswordRequest $request): JsonResponse
    {
        Password::broker('customers')->sendResetLink(
            $request->only('email')
        );

        return response()->json([
            'message' => 'Si existe una cuenta con ese email, recibirás un enlace para restablecer tu contraseña.',
        ]);
    }
}
