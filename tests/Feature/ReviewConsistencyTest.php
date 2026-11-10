<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReviewConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private function review(Product $product, int $rating, array $attributes = []): Review
    {
        return Review::create(array_merge([
            'product_id' => $product->id,
            'user_id' => User::factory()->create()->id,
            'rating' => $rating,
            'title' => 'Avis',
            'comment' => 'Texte de test.',
            'is_approved' => true,
        ], $attributes));
    }

    public function test_only_approved_reviews_move_the_public_stars(): void
    {
        $product = Product::factory()->create();
        $this->review($product, 5);
        $this->review($product, 3);
        $this->review($product, 1, ['is_approved' => false]);

        $product = $product->fresh();

        $this->assertSame(4.0, (float) $product->average_rating);
        $this->assertSame(2, $product->total_reviews);
    }

    public function test_quick_view_reports_only_approved_reviews(): void
    {
        $product = Product::factory()->create(['is_active' => true]);
        $this->review($product, 4);
        $this->review($product, 1, ['is_approved' => false]);

        $this->getJson(route('products.quickView', $product->slug))
            ->assertOk()
            ->assertJson(['reviews_count' => 1, 'reviews_avg_rating' => 4]);
    }

    public function test_a_review_without_an_explicit_approval_lands_in_moderation(): void
    {
        $product = Product::factory()->create();

        DB::table('reviews')->insert([
            'product_id' => $product->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 5,
            'title' => 'Sans valeur explicite',
            'comment' => 'Le défaut de colonne doit être "en attente".',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFalse(Review::sole()->is_approved);
    }

    public function test_a_customer_cannot_review_the_same_product_twice(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $payload = ['rating' => 5, 'title' => 'Top', 'comment' => 'Très bon produit.'];

        $this->actingAs($user)->post(route('products.reviews.store', $product->slug), $payload)
            ->assertSessionHas('success');
        $this->actingAs($user)->post(route('products.reviews.store', $product->slug), $payload)
            ->assertSessionHas('error');

        $this->assertSame(1, Review::where('user_id', $user->id)->count());
    }

    public function test_the_form_is_replaced_by_a_notice_once_the_customer_reviewed(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $this->review($product, 4, ['user_id' => $user->id]);

        $this->actingAs($user)->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('Vous avez déjà publié un avis sur ce produit.')
            ->assertDontSee('name="title"');
    }

    public function test_another_customer_still_sees_the_form(): void
    {
        $product = Product::factory()->create();
        $this->review($product, 4);

        $this->actingAs(User::factory()->create())->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('name="title"', false);
    }

    public function test_review_submissions_are_rate_limited(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $payload = ['rating' => 5, 'title' => 'Top', 'comment' => 'Très bon produit.'];

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->post(route('products.reviews.store', $product->slug), $payload);
        }

        $this->actingAs($user)->post(route('products.reviews.store', $product->slug), $payload)
            ->assertStatus(429);
    }
}
