<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestQuoteReuseTest extends TestCase
{
    use RefreshDatabase;

    private string $token = '62da3cd9-f9e9-4c42-a882-52e8086f9472';

    public function test_reuses_expired_quote_without_unique_violation(): void
    {
        Quote::create([
            'guest_token' => $this->token,
            'status'      => Quote::STATUS_EXPIRED,
            'expires_at'  => now()->subDay(),
        ]);

        $quote = Quote::getActiveForGuest($this->token);

        $this->assertSame(Quote::STATUS_ACTIVE, $quote->status);
        $this->assertSame(1, Quote::where('guest_token', $this->token)->count());
    }

    public function test_converted_quote_is_reused_empty(): void
    {
        $old = Quote::create([
            'guest_token' => $this->token,
            'status'      => Quote::STATUS_CONVERTED,
            'expires_at'  => now()->subDay(),
        ]);
        $product = Product::factory()->create(['stock' => 5]);
        $old->items()->create([
            'product_id'   => $product->id,
            'product_slug' => $product->slug,
            'product_name' => $product->name,
            'unit_price'   => 100,
            'quantity'     => 1,
            'subtotal'     => 100,
        ]);

        $quote = Quote::getActiveForGuest($this->token);

        $this->assertSame(Quote::STATUS_ACTIVE, $quote->status);
        $this->assertCount(0, $quote->items);
        $this->assertSame(1, Quote::where('guest_token', $this->token)->count());
    }

    public function test_add_to_cart_over_api_after_expired_quote(): void
    {
        Quote::create([
            'guest_token' => $this->token,
            'status'      => Quote::STATUS_EXPIRED,
            'expires_at'  => now()->subDay(),
        ]);
        $product = Product::factory()->create(['stock' => 5, 'product_type' => 'simple']);

        $this->withHeader('X-Guest-Token', $this->token)
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])
            ->assertStatus(201);
    }
}
