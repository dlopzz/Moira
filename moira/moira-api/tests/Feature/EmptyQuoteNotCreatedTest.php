<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El front pide GET /cart al montar, en cada visita al sitio. Antes eso insertaba
 * un Quote por visitante, llenando el listado del admin de carritos vacíos.
 */
class EmptyQuoteNotCreatedTest extends TestCase
{
    use RefreshDatabase;

    private string $token = '11111111-1111-1111-1111-111111111111';

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'first_name' => 'Ada',
            'last_name'  => 'Lovelace',
            'email'      => 'ada@test.local',
            'password'   => bcrypt('secret1234'),
            'is_active'  => true,
        ]);
    }

    public function test_guest_cart_read_does_not_create_a_quote(): void
    {
        $this->getJson('/api/v1/cart', ['X-Guest-Token' => $this->token])
            ->assertOk()
            ->assertJsonPath('data.summary.items_count', 0)
            ->assertJsonPath('data.items', []);

        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_guest_checkout_read_does_not_create_a_quote(): void
    {
        $this->getJson('/api/v1/guest-checkout', ['X-Guest-Token' => $this->token])
            ->assertOk()
            ->assertJsonPath('shipping_address', null)
            ->assertJsonPath('shipping_method', null);

        $this->getJson('/api/v1/guest-checkout/shipping-rates', ['X-Guest-Token' => $this->token])
            ->assertOk()
            ->assertJsonPath('data', []);

        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_adding_an_item_does_create_the_quote(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity'   => 1,
        ], ['X-Guest-Token' => $this->token])->assertCreated();

        $this->assertDatabaseCount('quotes', 1);

        // Y la lectura posterior devuelve ese mismo carrito, sin duplicarlo.
        $this->getJson('/api/v1/cart', ['X-Guest-Token' => $this->token])
            ->assertOk()
            ->assertJsonPath('data.summary.items_count', 1);

        $this->assertDatabaseCount('quotes', 1);
    }

    public function test_customer_cart_read_does_not_create_a_quote(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($customer, 'customer')
            ->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.summary.items_count', 0);

        $this->actingAs($customer, 'customer')
            ->getJson('/api/v1/checkout')
            ->assertOk()
            ->assertJsonPath('cart.summary.items_count', 0);

        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_customer_checkout_merges_a_guest_cart_that_has_items(): void
    {
        $customer = $this->makeCustomer();
        $product  = Product::factory()->create(['stock' => 5]);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity'   => 2,
        ], ['X-Guest-Token' => $this->token])->assertCreated();

        $this->actingAs($customer, 'customer')
            ->getJson('/api/v1/checkout', ['X-Guest-Token' => $this->token])
            ->assertOk()
            ->assertJsonPath('cart.summary.items_count', 2);

        $this->assertDatabaseHas('quotes', [
            'customer_id' => $customer->id,
            'status'      => Quote::STATUS_ACTIVE,
        ]);
    }

    public function test_prune_removes_empty_guest_quotes(): void
    {
        Quote::create([
            'guest_token' => $this->token,
            'status'      => Quote::STATUS_ACTIVE,
            'expires_at'  => now()->addDays(30),
        ])->forceFill(['created_at' => now()->subDays(3)])->save();

        $this->artisan('quotes:prune')->assertSuccessful();

        $this->assertDatabaseCount('quotes', 0);
    }
}
