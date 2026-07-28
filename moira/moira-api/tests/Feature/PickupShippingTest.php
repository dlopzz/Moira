<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Quote;
use App\Models\ShippingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PickupShippingTest extends TestCase
{
    use RefreshDatabase;

    private string $token = '62da3cd9-f9e9-4c42-a882-52e8086f9472';

    private function activatePickup(): ShippingMethod
    {
        $method = ShippingMethod::where('code', 'retiro_sucursal')->firstOrFail();
        $method->update([
            'is_active' => true,
            'config'    => [
                'pickup_address'  => 'Av. Siempreviva 742',
                'pickup_schedule' => 'Lun a Vie de 9 a 18 hs',
            ],
        ]);

        return $method;
    }

    private function guestQuoteWithItem(): Quote
    {
        $quote   = Quote::getActiveForGuest($this->token);
        $product = Product::factory()->create(['stock' => 5, 'product_type' => 'simple']);
        $quote->items()->create([
            'product_id'   => $product->id,
            'product_slug' => $product->slug,
            'product_name' => $product->name,
            'unit_price'   => 100,
            'quantity'     => 1,
            'subtotal'     => 100,
        ]);

        return $quote;
    }

    public function test_pickup_rate_is_available_without_address(): void
    {
        $this->activatePickup();
        $this->guestQuoteWithItem();

        $response = $this->withHeader('X-Guest-Token', $this->token)
            ->getJson('/api/v1/guest-checkout/shipping-rates')
            ->assertOk();

        $rate = collect($response->json('data'))->firstWhere('code', 'retiro_sucursal');

        $this->assertNotNull($rate);
        $this->assertTrue($rate['is_pickup']);
        $this->assertSame(0.0, (float) $rate['price']);
        $this->assertSame('Av. Siempreviva 742', $rate['pickup_address']);
    }

    public function test_select_pickup_forces_zero_cost(): void
    {
        $this->activatePickup();
        $quote = $this->guestQuoteWithItem();

        $this->withHeader('X-Guest-Token', $this->token)
            ->postJson('/api/v1/guest-checkout/shipping', [
                'code'  => 'retiro_sucursal',
                'label' => 'hackeado',
                'price' => 9999,
            ])
            ->assertOk();

        $quote->refresh();

        $this->assertSame('retiro_sucursal', $quote->shipping_method_code);
        $this->assertSame('Retiro en sucursal', $quote->shipping_method_label);
        $this->assertSame(0.0, (float) $quote->shipping_cost);
    }

    public function test_order_is_created_for_pickup_without_shipping_address(): void
    {
        $this->activatePickup();
        $quote = $this->guestQuoteWithItem();
        $quote->update([
            'guest_email'           => 'cliente@example.com',
            'shipping_firstname'    => 'Juan',
            'shipping_lastname'     => 'Pérez',
            'shipping_telephone'    => '1122334455',
            'shipping_method_code'  => 'retiro_sucursal',
            'shipping_method_label' => 'Retiro en sucursal',
            'shipping_cost'         => 0,
        ]);

        $response = $this->withHeader('X-Guest-Token', $this->token)
            ->postJson('/api/v1/guest-checkout/simulate-pay', ['result' => 'success'])
            ->assertStatus(201);

        $order = $response->json('data');

        $this->assertSame(0.0, (float) $order['shipping_cost']);
        $this->assertTrue($order['shipping_address']['pickup']);
        $this->assertSame('Av. Siempreviva 742', $order['shipping_address']['street']);
    }
}
