<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class RecaptchaVerifier
{
    public function verify(?string $token, string $ip): bool
    {
        try {
            $response = Http::asForm()
                ->timeout(5)
                ->connectTimeout(3)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => config('services.recaptcha.secret'),
                    'response' => $token ?? '',
                    'remoteip' => $ip,
                ]);
        } catch (ConnectionException) {
            return false;
        }

        return $response->json('success') ?? false;
    }

    public function failedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Verificación de reCAPTCHA fallida.',
            'errors' => ['recaptcha_token' => ['Por favor, completá la verificación.']],
        ], 422);
    }
}
