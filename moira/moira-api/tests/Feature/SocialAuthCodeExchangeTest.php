<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * El login con Google devolvía el token de Sanctum en la query string
 * (/auth/callback?token=...). Eso lo dejaba en el historial del navegador, en
 * los access logs de Traefik y del SSR, y lo filtraba por el header Referer de
 * cualquier request saliente de esa página.
 *
 * Ahora el callback deja un código de un solo uso y corto, que el front canjea
 * por POST. Estos tests fijan ese contrato.
 */
class SocialAuthCodeExchangeTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'first_name'        => 'Ada',
            'last_name'         => 'Lovelace',
            'email'             => 'ada@test.local',
            'password'          => bcrypt('secret1234'),
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
    }

    /** Deja un código válido en cache, igual que hace el callback de Google. */
    private function issueCode(Customer $customer): array
    {
        $token = $customer->createToken('api')->plainTextToken;
        $code  = Str::random(64);

        Cache::put('social-auth-code:'.hash('sha256', $code), $token, 120);

        return [$code, $token];
    }

    public function test_el_codigo_se_canjea_por_el_token(): void
    {
        $customer = $this->makeCustomer();
        [$code, $token] = $this->issueCode($customer);

        $response = $this->postJson('/api/v1/auth/social/exchange', ['code' => $code]);

        $response->assertOk();
        $this->assertSame($token, $response->json('token'));
    }

    public function test_el_codigo_sirve_una_sola_vez(): void
    {
        $customer = $this->makeCustomer();
        [$code] = $this->issueCode($customer);

        $this->postJson('/api/v1/auth/social/exchange', ['code' => $code])->assertOk();

        // Un código interceptado después de que el front lo usó ya no sirve.
        $this->postJson('/api/v1/auth/social/exchange', ['code' => $code])
            ->assertStatus(422);
    }

    public function test_un_codigo_inventado_no_devuelve_token(): void
    {
        $this->postJson('/api/v1/auth/social/exchange', ['code' => Str::random(64)])
            ->assertStatus(422);
    }

    public function test_el_codigo_expirado_no_sirve(): void
    {
        $customer = $this->makeCustomer();
        [$code] = $this->issueCode($customer);

        $this->travel(3)->minutes();

        $this->postJson('/api/v1/auth/social/exchange', ['code' => $code])
            ->assertStatus(422);
    }

    public function test_el_token_canjeado_autentica_de_verdad(): void
    {
        $customer = $this->makeCustomer();
        [$code] = $this->issueCode($customer);

        $token = $this->postJson('/api/v1/auth/social/exchange', ['code' => $code])
            ->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.email', 'ada@test.local');
    }
}
