<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /** Ventana para canjear el codigo. Corta: el front lo usa apenas carga. */
    private const CODE_TTL = 120;

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

        // El token NUNCA viaja en la URL: quedaria en el historial del navegador,
        // en los access logs de Traefik y del SSR, y se filtraria por el header
        // Referer de cualquier request saliente de esa pagina. En su lugar
        // mandamos un codigo de un solo uso, de vida corta, que el front canjea
        // por POST contra /auth/social/exchange.
        $token = $customer->createToken('api')->plainTextToken;
        $code  = Str::random(64);

        Cache::put(self::codeKey($code), $token, self::CODE_TTL);

        return redirect("{$frontend}/auth/callback?code={$code}");
    }

    /**
     * Canjea el codigo de un solo uso por el token de acceso.
     *
     * Cache::pull borra la clave al leerla, asi que un codigo interceptado
     * despues de que el front lo uso ya no sirve.
     */
    public function exchange(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:64'],
        ]);

        $token = Cache::pull(self::codeKey($request->input('code')));

        if (! $token) {
            return response()->json([
                'message' => 'El código de acceso no es válido o ya expiró.',
            ], 422);
        }

        return response()->json(['token' => $token]);
    }

    private static function codeKey(string $code): string
    {
        return 'social-auth-code:'.hash('sha256', $code);
    }
}
