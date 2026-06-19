<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Mail\CustomerVerifyEmail;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class VerifyEmailController extends Controller
{
    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $frontend = config('app.frontend_url', 'http://localhost:3000');

        $customer = Customer::findOrFail($id);

        if (! hash_equals($hash, sha1($customer->email))) {
            return redirect("{$frontend}/auth/verified?error=invalid");
        }

        if (! $request->hasValidSignature()) {
            return redirect("{$frontend}/auth/verified?error=expired");
        }

        if ($customer->hasVerifiedEmail()) {
            return redirect("{$frontend}/auth/verified?already=1");
        }

        $customer->markEmailAsVerified();

        return redirect("{$frontend}/auth/verified?success=1");
    }

    public function resend(Request $request): JsonResponse
    {
        $customer = $request->user();

        if ($customer->hasVerifiedEmail()) {
            return response()->json(['message' => 'El email ya fue verificado.'], 422);
        }

        Mail::to($customer->email)->queue(new CustomerVerifyEmail($customer));

        return response()->json(['message' => 'Email de verificación reenviado.']);
    }
}
