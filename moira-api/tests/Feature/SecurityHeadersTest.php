<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * securityheaders.com daba F en el sitio: no salía ninguna cabecera de
 * seguridad. Las de la API las pone Traefik, pero eso vive fuera del repo, así
 * que ahora también las manda un middleware propio.
 *
 * El riesgo del CSP es romper el panel: Filament necesita scripts y estilos
 * inline, por eso la política no declara script-src ni style-src. Este test fija
 * las dos cosas — que las cabeceras salgan, y que el admin siga respondiendo.
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_respuestas_de_la_api_traen_las_cabeceras_de_seguridad(): void
    {
        $response = $this->getJson('/api/v1/settings');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');
    }

    public function test_el_csp_no_restringe_los_scripts_ni_los_estilos_del_panel(): void
    {
        $csp = $this->getJson('/api/v1/settings')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);

        // Si alguna de estas dos aparece, Filament deja de renderizar.
        $this->assertStringNotContainsString('script-src', $csp);
        $this->assertStringNotContainsString('default-src', $csp);
    }

    public function test_el_login_del_panel_sigue_cargando(): void
    {
        $response = $this->get('/c4v14r/login');

        $response->assertOk();
        $response->assertSee('csrf-token', false);
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_hsts_solo_viaja_sobre_https(): void
    {
        $this->getJson('/api/v1/settings')
            ->assertHeaderMissing('Strict-Transport-Security');

        $this->getJson('https://localhost/api/v1/settings')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
