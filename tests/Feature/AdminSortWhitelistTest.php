<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\NewsletterSubscriber;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSortWhitelistTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_review_sorting_only_accepts_known_columns(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        Review::create(['product_id' => $product->id, 'user_id' => $user->id, 'rating' => 5, 'title' => 'Excellent', 'comment' => 'Parfait']);
        Review::create(['product_id' => $product->id, 'user_id' => $user->id, 'rating' => 1, 'title' => 'Décevant', 'comment' => 'Abîmé']);

        $admin = $this->admin();

        // An unknown column used to reach the SQL builder and fail with a 500
        $this->actingAs($admin)->get(route('admin.reviews.index', ['sort_by' => 'not_a_column', 'sort_order' => 'sideways']))->assertOk();
        $this->actingAs($admin)->get(route('admin.reviews.index', ['sort_by' => 'rating', 'sort_order' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['Décevant', 'Excellent']);
        $this->actingAs($admin)->get(route('admin.reviews.index', ['sort_by' => 'rating', 'sort_order' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder(['Excellent', 'Décevant']);
    }

    public function test_coupon_sorting_only_accepts_known_columns(): void
    {
        Coupon::factory()->create(['code' => 'PETIT', 'value' => 5]);
        Coupon::factory()->create(['code' => 'GRAND', 'value' => 40]);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.coupons.index', ['sort_by' => 'not_a_column', 'sort_order' => 'asc']))->assertOk();
        $this->actingAs($admin)->get(route('admin.coupons.index', ['sort_by' => 'value', 'sort_order' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['PETIT', 'GRAND']);
    }

    public function test_newsletter_sorting_only_accepts_known_columns(): void
    {
        NewsletterSubscriber::create(['email' => 'a@example.com', 'token' => str_repeat('a', 32), 'subscribed_at' => now()]);
        NewsletterSubscriber::create(['email' => 'b@example.com', 'token' => str_repeat('b', 32), 'subscribed_at' => now()]);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.newsletter.index', ['sort_by' => 'not_a_column', 'sort_order' => 'desc']))->assertOk();
        $this->actingAs($admin)->get(route('admin.newsletter.index', ['sort_by' => 'email', 'sort_order' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['a@example.com', 'b@example.com']);
    }

    public function test_sorting_screens_stay_admin_only(): void
    {
        $customer = User::factory()->create();

        $this->get(route('admin.reviews.index'))->assertRedirect();
        $this->actingAs($customer)->get(route('admin.reviews.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.coupons.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.newsletter.index'))->assertForbidden();
    }
}
