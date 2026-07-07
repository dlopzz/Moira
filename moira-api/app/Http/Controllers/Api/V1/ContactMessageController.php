<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Services\RecaptchaVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function store(Request $request, RecaptchaVerifier $recaptcha): JsonResponse
    {
        $recaptchaEnabled = SiteSetting::instance()->recaptcha_enabled;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'recaptcha_token' => [$recaptchaEnabled ? 'required' : 'nullable', 'string'],
        ]);

        if ($recaptchaEnabled) {
            $verified = $recaptcha->verify($validated['recaptcha_token'] ?? null, $request->ip());

            if (! $verified) {
                return $recaptcha->failedResponse();
            }
        }

        ContactMessage::create([
            'name' => $validated['name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'message' => $validated['message'],
        ]);

        return response()->json(['message' => 'Mensaje enviado correctamente.'], 201);
    }
}
