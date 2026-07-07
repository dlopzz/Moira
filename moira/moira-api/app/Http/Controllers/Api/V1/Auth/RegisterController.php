<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\Api\CustomerResource;
use App\Mail\CustomerVerifyEmail;
use App\Models\Customer;
use App\Models\SiteSetting;
use App\Services\RecaptchaVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    public function store(RegisterRequest $request, RecaptchaVerifier $recaptcha): JsonResponse
    {
        if (SiteSetting::instance()->recaptcha_enabled) {
            $verified = $recaptcha->verify($request->input('recaptcha_token'), $request->ip());

            if (! $verified) {
                return $recaptcha->failedResponse();
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
