<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El email identifica la cuenta y está ligado a email_verified_at.
 *
 * Antes se podía editar desde PUT /profile (y el front lo mandaba): el cliente
 * verificaba su cuenta, después la cambiaba a cualquier dirección y quedaba
 * marcada como verificada con un email que nadie verificó. Además el email es
 * único, así que dejarlo editable abre colisiones y bloqueos de registro.
 *
 * Estos tests fijan el comportamiento: PUT /profile actualiza el resto de los
 * campos y NUNCA toca el email.
 */
class ProfileEmailImmutableTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(array $attributes = []): Customer
    {
        return Customer::create(array_merge([
            'first_name'        => 'Ada',
            'last_name'         => 'Lovelace',
            'email'             => 'ada@test.local',
            'password'          => bcrypt('secret1234'),
            'is_active'         => true,
            'email_verified_at' => now(),
        ], $attributes));
    }

    public function test_email_no_cambia_aunque_se_mande_en_el_body(): void
    {
        $customer = $this->makeCustomer();

        $response = $this->actingAs($customer, 'customer')->putJson('/api/v1/profile', [
            'first_name' => 'Ada',
            'last_name'  => 'Byron',
            'email'      => 'atacante@evil.local',
        ]);

        $response->assertOk();

        $customer->refresh();

        $this->assertSame('ada@test.local', $customer->email);
        $this->assertSame('Byron', $customer->last_name, 'el resto del perfil sí se actualiza');
    }

    public function test_la_cuenta_sigue_verificada_con_su_email_original(): void
    {
        $customer = $this->makeCustomer();
        $verifiedAt = $customer->email_verified_at;

        $this->actingAs($customer, 'customer')->putJson('/api/v1/profile', [
            'first_name' => 'Ada',
            'last_name'  => 'Lovelace',
            'email'      => 'otro@evil.local',
        ])->assertOk();

        $customer->refresh();

        $this->assertSame('ada@test.local', $customer->email);
        $this->assertTrue($customer->hasVerifiedEmail());
        $this->assertEquals(
            $verifiedAt->timestamp,
            $customer->email_verified_at->timestamp,
            'no se debe re-verificar ni invalidar: el email nunca cambió'
        );
    }

    public function test_no_se_puede_pisar_el_email_de_otro_cliente(): void
    {
        $otro = $this->makeCustomer(['email' => 'otro@test.local']);
        $customer = $this->makeCustomer();

        $this->actingAs($customer, 'customer')->putJson('/api/v1/profile', [
            'first_name' => 'Ada',
            'last_name'  => 'Lovelace',
            'email'      => 'otro@test.local',
        ])->assertOk();

        $this->assertSame('ada@test.local', $customer->fresh()->email);
        $this->assertSame('otro@test.local', $otro->fresh()->email);
    }

    public function test_el_email_no_es_obligatorio_en_el_body(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($customer, 'customer')->putJson('/api/v1/profile', [
            'first_name' => 'Grace',
            'last_name'  => 'Hopper',
        ])->assertOk();

        $customer->refresh();

        $this->assertSame('Grace', $customer->first_name);
        $this->assertSame('ada@test.local', $customer->email);
    }
}
