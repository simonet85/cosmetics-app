<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReviewBulkTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function review(Product $product, User $user, array $overrides = []): Review
    {
        return Review::create(array_merge([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 4,
            'title' => 'Très bien',
            'comment' => 'Un produit agréable à utiliser.',
            'is_approved' => false,
        ], $overrides));
    }

    public function test_bulk_moderation_is_for_admins_only(): void
    {
        $this->post(route('admin.reviews.bulk'))->assertRedirect();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.reviews.bulk'), ['ids' => [1], 'action' => 'approve'])
            ->assertForbidden();
    }

    public function test_a_selection_can_be_approved_again_or_deleted(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $first = $this->review($product, $user);
        $second = $this->review($product, $user);
        $untouched = $this->review($product, $user);

        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.reviews.bulk'), [
            'ids' => [$first->id, $second->id], 'action' => 'approve',
        ])->assertRedirect()->assertSessionHas('success', '2 avis approuvé(s).');

        $this->assertTrue($first->fresh()->is_approved);
        $this->assertTrue($second->fresh()->is_approved);
        $this->assertFalse($untouched->fresh()->is_approved);

        $this->actingAs($admin)->post(route('admin.reviews.bulk'), [
            'ids' => [$first->id], 'action' => 'unapprove',
        ]);

        $this->assertFalse($first->fresh()->is_approved);

        $this->actingAs($admin)->post(route('admin.reviews.bulk'), [
            'ids' => [$second->id], 'action' => 'delete',
        ])->assertSessionHas('success', '1 avis supprimé(s).');

        $this->assertModelMissing($second);
        $this->assertModelExists($first);
    }

    public function test_a_selection_is_capped_and_an_unknown_action_is_refused(): void
    {
        $product = Product::factory()->create();
        $review = $this->review($product, User::factory()->create());

        $this->actingAs($this->admin())
            ->post(route('admin.reviews.bulk'), ['ids' => range(1, 101), 'action' => 'approve'])
            ->assertSessionHasErrors('ids');

        $this->actingAs($this->admin())
            ->post(route('admin.reviews.bulk'), ['ids' => [$review->id], 'action' => 'publish'])
            ->assertSessionHasErrors('action');

        $this->assertFalse($review->fresh()->is_approved);
    }

    public function test_a_review_is_a_verified_purchase_only_when_the_customer_bought_the_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5, 'is_active' => true]);
        $other = Product::factory()->create(['stock' => 5, 'is_active' => true]);

        $order = Order::factory()->for($user)->paid()->create();
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku,
            'quantity' => 1, 'price' => 1000, 'total' => 1000,
        ]);

        $this->actingAs($user)->post(route('products.reviews.store', $product->slug), [
            'rating' => 5, 'title' => 'Parfait', 'comment' => 'Je recommande.',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('products.reviews.store', $other->slug), [
            'rating' => 4, 'title' => 'Ailleurs', 'comment' => 'Pas encore testé.',
        ])->assertRedirect();

        $this->assertTrue(Review::where('product_id', $product->id)->sole()->is_verified_purchase);
        $this->assertFalse(Review::where('product_id', $other->id)->sole()->is_verified_purchase);
    }

    public function test_an_unpaid_order_does_not_verify_the_purchase(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5, 'is_active' => true]);

        $order = Order::factory()->for($user)->create(['payment_status' => 'pending', 'status' => 'pending']);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku,
            'quantity' => 1, 'price' => 1000, 'total' => 1000,
        ]);

        $this->actingAs($user)->post(route('products.reviews.store', $product->slug), [
            'rating' => 5, 'title' => 'En attente', 'comment' => 'À voir.',
        ]);

        $this->assertFalse(Review::sole()->is_verified_purchase);
    }

    public function test_the_admin_can_filter_verified_purchases(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();

        $this->review($product, $user, ['title' => 'Achat confirmé', 'is_verified_purchase' => true, 'is_approved' => true]);
        $this->review($product, $user, ['title' => 'Simple avis', 'is_verified_purchase' => false, 'is_approved' => true]);

        $this->actingAs($this->admin())
            ->get(route('admin.reviews.index', ['verified' => '1']))
            ->assertOk()
            ->assertSee('Achat confirmé')
            ->assertDontSee('Simple avis');
    }
}
