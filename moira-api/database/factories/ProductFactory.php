<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name'              => ucwords($name),
            'slug'              => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'sku'               => strtoupper(fake()->unique()->bothify('??-###')),
            'short_description' => fake()->sentence(),
            'description'       => fake()->paragraph(3),
            'meta_title'        => ucwords($name),
            'meta_description'  => fake()->sentence(),
            'price'             => fake()->randomFloat(2, 30000, 250000),
            'sale_price'        => null,
            'stock'             => fake()->numberBetween(0, 100),
            'images'            => [],
            'is_active'         => true,
            'product_type'      => 'simple',
        ];
    }

    public function configurable(): static
    {
        return $this->state(['product_type' => 'configurable', 'stock' => 0]);
    }

    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'sale_price' => round($attributes['price'] * 0.8, 2),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
