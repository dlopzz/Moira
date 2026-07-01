<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\Api\CustomerResource;
use App\Mail\CustomerVerifyEmail;
use App\Models\Customer;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    public function store(RegisterRequest $request): JsonResponse
    {
        if (SiteSetting::instance()->recaptcha_enabled) {
            $token = $request->input('recaptcha_token', '');
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret'   => config('services.recaptcha.secret'),
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

            if (! ($response->json('success') ?? false)) {
                return response()->json([
                    'message' => 'Verificación de reCAPTCHA fallida.',
                    'errors'  => ['recaptcha_token' => ['Por favor, completá la verificación.']],
                ], 422);
            }
        }

        $customer = Customer::create([
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'email'         => $request->email,
            'password'      => $request->password,
            'date_of_birth' => $request->date_of_birth,
            'is_active'     => true,
        ]);

        Mail::to($customer->email)->queue(new CustomerVerifyEmail($customer));

        return response()->json([
            'data'    => new CustomerResource($customer),
            'message' => 'Cuenta creada. Revisá tu email para verificar tu cuenta.',
        ], 201);
    }
}
