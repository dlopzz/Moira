<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

/**
 * El .env.example de desarrollo trae APP_DEBUG=true y LOG_LEVEL=debug. Si el
 * servidor se monta copiando ese archivo en vez de .env.production.example,
 * cualquier error devuelve el stack trace completo: rutas del filesystem,
 * queries con bindings y el contenido del entorno, credenciales incluidas.
 *
 * El guard corta el arranque en ese caso, así el deploy no levanta en vez de
 * servir una página que filtra la configuración.
 */
class ProductionDebugGuardTest extends TestCase
{
    /** Corre el guard con un entorno y un flag de debug dados. */
    private function boot(string $env, bool $debug): void
    {
        $this->app->detectEnvironment(fn () => $env);
        config()->set('app.debug', $debug);

        $method = new ReflectionMethod(AppServiceProvider::class, 'assertDebugIsOffInProduction');
        $method->setAccessible(true);
        $method->invoke(new AppServiceProvider($this->app));
    }

    public function test_produccion_con_debug_activo_no_arranca(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/APP_DEBUG=true/');

        $this->boot('production', true);
    }

    public function test_produccion_con_debug_apagado_arranca(): void
    {
        $this->boot('production', false);

        $this->assertTrue(true, 'no debe lanzar excepción');
    }

    public function test_local_con_debug_activo_arranca(): void
    {
        // En desarrollo debug=true es lo esperado y no debe molestar.
        $this->boot('local', true);

        $this->assertTrue(true, 'no debe lanzar excepción');
    }

    public function test_el_env_de_produccion_no_trae_debug_activo(): void
    {
        $example = file_get_contents(base_path('.env.production.example'));

        $this->assertStringContainsString('APP_DEBUG=false', $example);
        $this->assertStringNotContainsString('APP_DEBUG=true', $example);
        $this->assertStringNotContainsString('LOG_LEVEL=debug', $example);
    }
}
