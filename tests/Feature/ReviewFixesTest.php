<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\CheckoutToken;
use Illuminate\Cache\ArrayStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Regression tests for the points of the final code review. */
class ReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_current_password_without_a_new_one_is_a_validation_error_not_a_crash(): void
    {
        $user = User::factory()->create(['password' => bcrypt('old-password-1')]);

        $this->actingAs($user)->put(route('account.profile.update'), [
            'first_name' => 'Awa', 'last_name' => 'Koné', 'email' => $user->email,
            'current_password' => 'old-password-1',
        ])->assertSessionHasErrors('new_password');

        $this->assertTrue(Hash::check('old-password-1', $user->fresh()->password));
    }

    public function test_the_profile_still_saves_without_touching_the_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('old-password-1')]);

        $this->actingAs($user)->put(route('account.profile.update'), [
            'first_name' => 'Awa', 'last_name' => 'Koné', 'email' => $user->email,
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('old-password-1', $user->fresh()->password));
    }

    public function test_an_unknown_sort_value_shows_the_shop_in_the_default_order(): void
    {
        Product::factory()->create(['name' => 'Huile test', 'is_active' => true, 'stock' => 3]);

        $this->get(route('shop.index', ['sort' => 'rating']))->assertOk()->assertSee('Huile test');
        $this->get(route('shop.index', ['sort' => 'name_desc']))->assertOk();
    }

    public function test_raising_the_quantity_of_a_line_whose_option_was_deleted_is_refused(): void
    {
        $product = Product::factory()->create(['stock' => 50, 'is_active' => true]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock' => 2, 'is_active' => true]);
        $session = ['cart' => [$product->id.'_'.$variant->id => ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1]]];
        $variant->delete();

        $this->withSession($session)->postJson(route('cart.update'), ['cart_item_id' => $product->id.'_'.$variant->id, 'quantity' => 20])
            ->assertStatus(400)->assertJson(['success' => false]);
    }

    public function test_raising_the_quantity_of_a_line_whose_option_was_switched_off_is_refused(): void
    {
        $product = Product::factory()->create(['stock' => 50, 'is_active' => true]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock' => 9, 'is_active' => true]);
        $session = ['cart' => [$product->id.'_'.$variant->id => ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1]]];
        $variant->update(['is_active' => false]);

        $this->withSession($session)->postJson(route('cart.update'), ['cart_item_id' => $product->id.'_'.$variant->id, 'quantity' => 2])
            ->assertStatus(400)->assertJson(['success' => false]);
    }

    public function test_a_damaged_line_in_the_browser_cart_does_not_break_the_sign_in(): void
    {
        $user = User::factory()->create(['email' => 'client@example.com', 'password' => bcrypt('secret-pass-1')]);
        $good = Product::factory()->create(['stock' => 10, 'is_active' => true]);

        $this->withSession(['cart' => [
            'broken' => ['product_id' => $good->id],                    // no quantity
            'empty' => [],
            'ok' => ['product_id' => $good->id, 'variant_id' => null, 'quantity' => 2],
        ]])->post(route('login.post'), ['email' => 'client@example.com', 'password' => 'secret-pass-1'])->assertRedirect();

        $this->assertSame(2, (int) Cart::where('user_id', $user->id)->sum('quantity'));
    }

    public function test_a_cache_failure_after_the_order_is_saved_does_not_report_a_failed_order(): void
    {
        $product = Product::factory()->create(['price' => 1000, 'stock' => 5, 'is_active' => true]);

        // A cache store that refuses to remember the order, as a Redis outage would
        Cache::extend('failing', fn () => Cache::repository(new class extends ArrayStore
        {
            public function put($key, $value, $seconds)
            {
                if (str_ends_with($key, ':order')) {
                    throw new \RuntimeException('cache down');
                }

                return parent::put($key, $value, $seconds);
            }
        }));
        config(['cache.stores.failing' => ['driver' => 'failing'], 'cache.default' => 'failing']);

        $this->withSession(['cart' => [(string) $product->id => ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]]])
            ->post(route('checkout.process'), [
                'checkout_token' => CheckoutToken::make(),
                'first_name' => 'Awa', 'last_name' => 'Koné', 'email' => 'awa@example.com',
                'phone' => '0701020304', 'city' => 'Abidjan', 'quartier' => 'Cocody',
                'payment_method' => 'cash_on_delivery', 'billing_same_as_shipping' => '1',
            ])->assertSessionHas('success')->assertSessionMissing('error');

        $this->assertSame(1, Order::count());
    }
}
