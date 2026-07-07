<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\SiteSetting;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (mixed $notifiable, string $token): string {
            $storedUrl = SiteSetting::getValue('url');
            $frontendUrl = rtrim(
                $storedUrl ?: config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')),
                '/'
            );

            return "{$frontendUrl}/auth/reset-password?token={$token}&email=".urlencode($notifiable->email);
        });

        // Gate nombrado (no una Policy de Order) a propósito: una Policy quedaría
        // auto-descubierta por Laravel para el modelo Order y Filament la aplicaría
        // también al panel admin (autenticado como User, no Customer), rompiendo la
        // vista de órdenes. Este gate solo lo llama la API de clientes.
        // El parámetro no tipa Customer directamente como defensa extra: aunque
        // las rutas de este gate ya usan el guard 'customer' (ver routes/api.php),
        // un User con token futuro no debe crashear con un TypeError si este gate
        // se reutiliza alguna vez desde otro contexto.
        Gate::define('view-order', function (mixed $user, Order $order): bool {
            return $user instanceof Customer && $order->customer_id === $user->id;
        });
    }
}
