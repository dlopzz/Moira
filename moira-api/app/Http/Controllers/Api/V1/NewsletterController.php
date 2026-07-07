<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterWelcomeMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($request->email));
        $subscriber = NewsletterSubscriber::where('email', $email)->first();

        if ($subscriber) {
            if ($subscriber->unsubscribed_at === null) {
                return response()->json(['message' => 'Ya estás suscripto.']);
            }

            // Reactivate
            $subscriber->update(['unsubscribed_at' => null]);
            Mail::to($email)->queue(new NewsletterWelcomeMail($subscriber));

            return response()->json(['message' => 'Suscripción confirmada.'], 201);
        }

        $subscriber = NewsletterSubscriber::create(['email' => $email]);
        Mail::to($email)->queue(new NewsletterWelcomeMail($subscriber));

        return response()->json(['message' => 'Suscripción confirmada.'], 201);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $subscriber = NewsletterSubscriber::where('token', $request->query('token'))->first();

        if (! $subscriber) {
            return response()->json(['message' => 'Link inválido o ya procesado.'], 404);
        }

        $subscriber->update(['unsubscribed_at' => now()]);

        return response()->json(['message' => 'Te desuscribiste correctamente.']);
    }
}
