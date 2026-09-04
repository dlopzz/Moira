<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad para todas las respuestas de la API.
 *
 * Traefik ya inyecta HSTS, nosniff, X-Frame-Options y Referrer-Policy, pero eso
 * vive fuera del repo: definirlas también acá las deja versionadas y sobrevive
 * a un cambio de proxy. Las que faltaban son Permissions-Policy y el CSP.
 *
 * El CSP es deliberadamente corto: el panel de Filament necesita inline scripts
 * y styles, así que un default-src estricto lo rompe. Se limita a lo que aplica
 * igual a JSON y a HTML — nada de plugins, nadie nos puede embeber, y las URLs
 * relativas no se pueden reescribir con un <base> inyectado.
 */
class SecurityHeaders
{
    private const HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), interest-cohort=()',
        'Content-Security-Policy' => "frame-ancestors 'self'; object-src 'none'; base-uri 'self'",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADERS as $header => $value) {
            // No pisar lo que ya haya puesto el proxy: si Traefik cambia un
            // valor, gana el del borde y esto queda como red de seguridad.
            if (! $response->headers->has($header)) {
                $response->headers->set($header, $value);
            }
        }

        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
                false,
            );
        }

        return $response;
    }
}
