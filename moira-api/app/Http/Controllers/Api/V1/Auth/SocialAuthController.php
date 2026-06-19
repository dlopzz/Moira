<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $frontend = config('app.frontend_url', 'http://localhost:3000');

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect("{$frontend}/auth/callback?error=google_failed");
        }

        $customer = Customer::where('google_id', $googleUser->getId())->first()
            ?? Customer::where('email', $googleUser->getEmail())->first();

        if ($customer) {
            if (! $customer->google_id) {
                $customer->update(['google_id' => $googleUser->getId()]);
            }
            if (! $customer->hasVerifiedEmail()) {
                $customer->markEmailAsVerified();
            }
        } else {
            $parts     = explode(' ', $googleUser->getName() ?? 'Usuario', 2);
            $firstName = $parts[0];
            $lastName  = $parts[1] ?? '-';

            $customer = Customer::create([
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'email'             => $googleUser->getEmail(),
                'password'          => \Illuminate\Support\Str::random(32),
                'google_id'         => $googleUser->getId(),
                'email_verified_at' => now(),
                'is_active'         => true,
            ]);
        }

        if (! $customer->is_active) {
            return redirect("{$frontend}/auth/callback?error=account_disabled");
        }

        $token = $customer->createToken('api')->plainTextToken;

        return redirect("{$frontend}/auth/callback?token={$token}");
    }
}
