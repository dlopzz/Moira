<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartStockLimitTest extends TestCase
{
    use RefreshDatabase;

    private string $guestToken = '11111111-1111-4111-8111-111111111111';

    private function addItem(int $productId, int $qty): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('X-Guest-Token', $this->guestToken)
            ->postJson('/api/v1/cart/items', ['product_id' => $productId, 'quantity' => $qty]);
    }

    public function test_cannot_add_more_than_stock_in_a_single_request(): void
    {
        $product = Product::factory()->create(['stock' => 6, 'product_type' => 'simple']);

        $this->addItem($product->id, 7)
            ->assertStatus(422)
            ->assertJson(['message' => 'Stock insuficiente. Disponible: 6.']);
    }

    public function test_cannot_exceed_stock_when_merging_with_existing_item(): void
    {
        $product = Product::factory()->create(['stock' => 6, 'product_type' => 'simple']);

        $this->addItem($product->id, 6)->assertStatus(201);

        // Segundo add: 6 + 1 = 7 > 6 → rechazado.
        $this->addItem($product->id, 1)
            ->assertStatus(422)
            ->assertJson(['message' => 'Stock insuficiente. Disponible: 6.']);
    }

    public function test_update_item_cannot_exceed_stock(): void
    {
        $product = Product::factory()->create(['stock' => 6, 'product_type' => 'simple']);

        $this->addItem($product->id, 1)->assertStatus(201);

        $item = \App\Models\Quote::getActiveForGuest($this->guestToken)->items()->first();

        $this->withHeader('X-Guest-Token', $this->guestToken)
            ->putJson("/api/v1/cart/items/{$item->id}", ['quantity' => 10])
            ->assertStatus(422)
            ->assertJson(['message' => 'Stock insuficiente. Disponible: 6.']);
    }
}
