<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DuplicateProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_duplicating_a_product_creates_an_inactive_copy_with_unique_slug_and_no_sku(): void
    {
        $product = Product::factory()->create([
            'name' => 'Zapatilla Runner',
            'sku'  => 'RUN-001',
        ]);

        Livewire::test(ListProducts::class)
            ->callAction(TestAction::make('replicate')->table($product));

        $copy = Product::where('id', '!=', $product->id)->latest('id')->first();

        $this->assertNotNull($copy);
        $this->assertSame('Zapatilla Runner (copia)', $copy->name);
        $this->assertSame('zapatilla-runner-copia', $copy->slug);
        $this->assertNull($copy->sku);
        $this->assertFalse($copy->is_active);
    }

    public function test_slug_avoids_collisions_including_soft_deleted(): void
    {
        $product = Product::factory()->create(['name' => 'Botita Cuero']);
        Product::factory()->create(['slug' => 'botita-cuero-copia']);
        Product::factory()->create(['slug' => 'botita-cuero-copia-2'])->delete();

        Livewire::test(ListProducts::class)
            ->callAction(TestAction::make('replicate')->table($product));

        $copy = Product::where('name', 'Botita Cuero (copia)')->firstOrFail();

        $this->assertSame('botita-cuero-copia-3', $copy->slug);
    }

    public function test_duplicating_copies_categories_related_products_and_variants(): void
    {
        $categories = collect(['Calzado', 'Verano'])->map(fn (string $name) => Category::create([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
        ]));
        $related = Product::factory()->count(2)->create();

        $product = Product::factory()->configurable()->create();
        $product->categories()->sync($categories->pluck('id'));
        $product->relatedProducts()->sync($related->pluck('id'));

        foreach (['35', '36', '37'] as $index => $talle) {
            ProductVariant::create([
                'product_id' => $product->id,
                'sku'        => 'VAR-'.$talle,
                'price'      => 50000,
                'stock'      => 5,
                'attributes' => ['talle' => $talle],
                'sort_order' => $index,
                'is_active'  => true,
            ]);
        }

        Livewire::test(ListProducts::class)
            ->callAction(TestAction::make('replicate')->table($product));

        $copy = Product::where('id', '!=', $product->id)
            ->where('name', 'like', '%(copia)')
            ->latest('id')
            ->firstOrFail();

        $this->assertEqualsCanonicalizing(
            $categories->pluck('id')->all(),
            $copy->categories->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            $related->pluck('id')->all(),
            $copy->relatedProducts->pluck('id')->all(),
        );

        $this->assertCount(3, $copy->variants);
        $this->assertTrue($copy->variants->every(fn ($variant) => $variant->sku === null));
        $this->assertTrue($copy->variants->every(fn ($variant) => $variant->product_id === $copy->id));
    }
}
