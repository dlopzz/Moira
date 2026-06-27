<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku'        => strtoupper(fake()->unique()->bothify('VAR-###-??')),
            'price'      => fake()->randomFloat(2, 30000, 250000),
            'stock'      => fake()->numberBetween(0, 50),
            'attributes' => ['talle' => (string) fake()->randomElement([35, 36, 37, 38, 39, 40, 41, 42])],
            'sort_order' => 0,
            'is_active'  => true,
        ];
    }
}
