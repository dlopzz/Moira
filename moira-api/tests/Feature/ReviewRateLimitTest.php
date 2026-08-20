<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Las rutas de reseña eran las únicas públicas que quedaban fuera de todo grupo
 * de throttle. El token es un UUID, así que no es forzable por fuerza bruta,
 * pero sin límite un bot puede martillarlas gratis: cada GET pega a la base y
 * cada POST intenta escribir.
 */
class ReviewRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private function makeReview(): Review
    {
        $customer = Customer::create([
            'first_name'        => 'Ada',
            'last_name'         => 'Lovelace',
            'email'             => 'ada@test.local',
            'password'          => bcrypt('secret1234'),
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        return Review::create([
            'product_id'  => Product::factory()->create()->id,
            'customer_id' => $customer->id,
            'token'       => (string) Str::uuid(),
            'is_approved' => false,
        ]);
    }

    public function test_el_get_de_resenas_tiene_limite(): void
    {
        $review = $this->makeReview();

        // El límite es 20/min: la 21 debe cortar.
        for ($i = 0; $i < 20; $i++) {
            $this->getJson("/api/v1/reviews/{$review->token}")->assertOk();
        }

        $this->getJson("/api/v1/reviews/{$review->token}")->assertStatus(429);
    }

    public function test_el_post_de_resenas_tiene_limite(): void
    {
        $review = $this->makeReview();

        $payload = [
            'rating' => 5,
            'body'   => 'Muy buen producto, la calidad es excelente.',
        ];

        // Límite 5/min. La primera consume el token (submitted_at), así que las
        // siguientes dan 404; lo que importa es que a partir de la 6ª sea 429.
        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/v1/reviews/{$review->token}", $payload);
        }

        $this->postJson("/api/v1/reviews/{$review->token}", $payload)
            ->assertStatus(429);
    }

    public function test_un_token_inexistente_tambien_esta_limitado(): void
    {
        // Sin esto, un bot podría probar tokens sin costo alguno.
        $token = (string) Str::uuid();

        for ($i = 0; $i < 20; $i++) {
            $this->getJson("/api/v1/reviews/{$token}")->assertNotFound();
        }

        $this->getJson("/api/v1/reviews/{$token}")->assertStatus(429);
    }
}
