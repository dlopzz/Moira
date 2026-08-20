<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\SiteSetting;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->assertDebugIsOffInProduction();

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

        // Rate limiter del catálogo público. El tráfico SSR de moira-web presenta
        // X-Internal-Key (server-only) y queda exento: sus requests llegan todas
        // con la IP del contenedor, así que un límite por-IP las agruparía en un
        // único balde compartido y tumbaría el sitio bajo carga. Todo lo demás
        // —navegador del cliente final o un scraper que pega directo a la API— se
        // limita por IP real.
        RateLimiter::for('catalog', function (Request $request): Limit {
            $key = (string) config('services.internal.key');

            if ($key !== '' && hash_equals($key, (string) $request->header('X-Internal-Key'))) {
                return Limit::none();
            }

            return Limit::perMinute(60)->by($request->ip());
        });

        // Rate limiter del carrito. Sumar/restar cantidades puede generar varias
        // requests seguidas; un tope alto evita el 429 en uso normal sin dejar el
        // endpoint sin protección anti-abuso. El SSR interno queda exento igual que
        // en 'catalog'.
        RateLimiter::for('cart', function (Request $request): Limit {
            $key = (string) config('services.internal.key');

            if ($key !== '' && hash_equals($key, (string) $request->header('X-Internal-Key'))) {
                return Limit::none();
            }

            return Limit::perMinute(300)->by($request->ip());
        });
    }

    /**
     * Aborta el arranque si producción quedó con APP_DEBUG=true.
     *
     * El .env.example (el de desarrollo local) trae APP_DEBUG=true y
     * LOG_LEVEL=debug. Si alguien monta el servidor copiando ese archivo en vez
     * de .env.production.example, Laravel muestra el stack trace completo ante
     * cualquier error: rutas del filesystem, queries con sus bindings y el
     * contenido del entorno, incluidas las credenciales.
     *
     * Falla en el arranque, no en el primer error: preferimos que el deploy no
     * levante antes que servir una página que filtra la configuración. Es el
     * mismo criterio que usa Laravel cuando falta APP_KEY.
     */
    private function assertDebugIsOffInProduction(): void
    {
        if ($this->app->isProduction() && config('app.debug')) {
            throw new RuntimeException(
                'APP_DEBUG=true con APP_ENV=production. Los errores expondrían rutas, '
                .'queries y variables de entorno. Revisá el .env del servidor: '
                .'debe salir de .env.production.example, no de .env.example.'
            );
        }
    }
}
