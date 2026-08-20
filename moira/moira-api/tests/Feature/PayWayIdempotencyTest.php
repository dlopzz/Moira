<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Services\Payment\PayWayProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * PayWayProvider generaba un site_transaction_id nuevo en cada llamada.
 *
 * Si la request muere por timeout DESPUÉS de que PayWay procesó el cobro, el
 * reintento llegaba con un id distinto y el cliente pagaba dos veces. Ahora el
 * id se deriva del quote y del monto, así que un reintento manda exactamente el
 * mismo site_transaction_id y PayWay lo rechaza por duplicado.
 */
class PayWayIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function method(): PaymentMethod
    {
        return PaymentMethod::create([
            'code'        => 'payway',
            'name'        => 'PayWay',
            'is_active'   => true,
            'is_sandbox'  => true,
            'credentials' => ['sandbox_private_key' => 'test-private-key'],
            'config'      => ['endpoint_sandbox' => 'https://payway.test'],
        ]);
    }

    private function fakeApproved(): void
    {
        Http::fake([
            '*' => Http::response([
                'id'                  => 123,
                'status'              => 'approved',
                'site_transaction_id' => 'whatever',
                'status_details'      => ['card_authorization_code' => 'ABC123'],
            ], 200),
        ]);
    }

    /** @return array<string> los site_transaction_id enviados a PayWay */
    private function sentSiteIds(): array
    {
        $ids = [];

        Http::recorded(function ($request) use (&$ids) {
            $ids[] = $request->data()['site_transaction_id'] ?? null;

            return true;
        });

        return $ids;
    }

    private function tokenData(): array
    {
        return [
            'token'             => 'card-token',
            'bin'               => '450799',
            'payment_method_id' => 1,
            'installments'      => 1,
        ];
    }

    public function test_la_misma_clave_produce_el_mismo_site_transaction_id(): void
    {
        $this->fakeApproved();
        $provider = new PayWayProvider($this->method());

        $provider->charge('ada@test.local', 150000, $this->tokenData(), idempotencyKey: 'quote-7-150000');
        $provider->charge('ada@test.local', 150000, $this->tokenData(), idempotencyKey: 'quote-7-150000');

        $ids = $this->sentSiteIds();

        $this->assertCount(2, $ids);
        $this->assertSame($ids[0], $ids[1], 'un reintento debe reusar el id para que PayWay lo rechace');
    }

    public function test_claves_distintas_producen_ids_distintos(): void
    {
        $this->fakeApproved();
        $provider = new PayWayProvider($this->method());

        $provider->charge('ada@test.local', 150000, $this->tokenData(), idempotencyKey: 'quote-7-150000');
        $provider->charge('ada@test.local', 150000, $this->tokenData(), idempotencyKey: 'quote-8-150000');

        $ids = $this->sentSiteIds();

        $this->assertNotSame($ids[0], $ids[1], 'dos compras reales no deben colisionar');
    }

    public function test_un_monto_distinto_es_un_cobro_distinto(): void
    {
        $this->fakeApproved();
        $provider = new PayWayProvider($this->method());

        $provider->charge('ada@test.local', 150000, $this->tokenData(), idempotencyKey: 'quote-7-150000');
        $provider->charge('ada@test.local', 200000, $this->tokenData(), idempotencyKey: 'quote-7-200000');

        $ids = $this->sentSiteIds();

        $this->assertNotSame($ids[0], $ids[1]);
    }

    public function test_sin_clave_cae_al_id_aleatorio(): void
    {
        $this->fakeApproved();
        $provider = new PayWayProvider($this->method());

        $provider->charge('ada@test.local', 150000, $this->tokenData());
        $provider->charge('ada@test.local', 150000, $this->tokenData());

        $ids = $this->sentSiteIds();

        $this->assertNotSame($ids[0], $ids[1]);
    }
}
