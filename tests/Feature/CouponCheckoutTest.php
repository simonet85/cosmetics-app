<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Services\CouponService;
use App\Support\CheckoutToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function cart(Product $product, int $quantity = 1): array
    {
        return ['cart' => [(string) $product->id => ['product_id' => $product->id, 'variant_id' => null, 'quantity' => $quantity]]];
    }

    private function checkoutData(): array
    {
        return [
            'checkout_token' => CheckoutToken::make(),
            'first_name' => 'Awa', 'last_name' => 'Koné', 'email' => 'awa@example.com',
            'phone' => '0701020304', 'city' => 'Abidjan', 'quartier' => 'Cocody',
            'payment_method' => 'cash_on_delivery', 'billing_same_as_shipping' => '1',
        ];
    }

    public function test_a_percentage_coupon_applies_to_the_checkout_page(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5]);
        Coupon::factory()->create(['code' => 'PROMO10', 'type' => 'percentage', 'value' => 10]);

        $this->withSession($this->cart($product));

        $this->post(route('checkout.coupon.apply'), ['coupon' => 'promo10'])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas(CouponService::SESSION_KEY, 'PROMO10');

        $html = $this->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Réduction (PROMO10)', $html);
        $this->assertStringContainsString(format_fcfa(1000), $html);
        // 9 000 + 15 % de TVA (1 350) = 10 350
        $this->assertStringContainsString(format_fcfa(10350), $html);
    }

    public function test_the_order_records_the_discount_and_counts_one_use(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5]);
        $coupon = Coupon::factory()->create(['code' => 'PROMO10', 'type' => 'percentage', 'value' => 10]);

        $this->withSession($this->cart($product));
        $this->post(route('checkout.coupon.apply'), ['coupon' => 'PROMO10']);

        $this->post(route('checkout.process'), $this->checkoutData())->assertRedirect();

        $order = Order::sole();

        $this->assertSame('PROMO10', $order->coupon_code);
        $this->assertEquals(1000, (float) $order->discount);
        $this->assertEquals(10000, (float) $order->subtotal);
        $this->assertEquals(1350, (float) $order->tax);
        $this->assertEquals(10350, (float) $order->total);
        $this->assertSame(1, $coupon->fresh()->used_count);
        $this->assertNull(session(CouponService::SESSION_KEY));

        // The customer is not charged the full price a second time
        $this->assertSame(1, Order::count());
    }

    public function test_a_percentage_coupon_never_exceeds_its_max_discount(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5]);
        Coupon::factory()->create(['code' => 'BIG', 'type' => 'percentage', 'value' => 50, 'max_discount' => 1200]);

        $this->withSession($this->cart($product));
        $this->post(route('checkout.coupon.apply'), ['coupon' => 'BIG'])->assertSessionMissing('error');
        $this->post(route('checkout.process'), $this->checkoutData())->assertRedirect();

        $this->assertEquals(1200, (float) Order::sole()->discount);
    }

    public function test_a_fixed_coupon_is_capped_by_the_cart_value(): void
    {
        $product = Product::factory()->create(['price' => 800, 'stock' => 5]);
        Coupon::factory()->fixed(5000)->create(['code' => 'GROS']);

        $this->withSession($this->cart($product));
        $this->post(route('checkout.coupon.apply'), ['coupon' => 'GROS'])->assertSessionMissing('error');
        $this->post(route('checkout.process'), $this->checkoutData())->assertRedirect();

        $order = Order::sole();

        $this->assertEquals(800, (float) $order->discount);
        $this->assertEquals(0, (float) $order->total);
    }

    public function test_unusable_coupons_are_refused_with_a_reason(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5]);
        $this->withSession($this->cart($product));

        Coupon::factory()->create(['code' => 'OFF', 'is_active' => false]);
        Coupon::factory()->create(['code' => 'LATE', 'end_date' => now()->subDay()]);
        Coupon::factory()->create(['code' => 'SOON', 'start_date' => now()->addDay()]);
        Coupon::factory()->create(['code' => 'USED', 'usage_limit' => 1, 'used_count' => 1]);
        Coupon::factory()->create(['code' => 'MINI', 'min_amount' => 20000]);

        $cases = [
            'NOPE' => 'n\'existe pas',
            'OFF' => 'n\'est plus actif',
            'LATE' => 'a expiré',
            'SOON' => 'pas encore valable',
            'USED' => 'limite d\'utilisation',
            'MINI' => 'au moins',
        ];

        foreach ($cases as $code => $expected) {
            $this->post(route('checkout.coupon.apply'), ['coupon' => $code])
                ->assertRedirect()
                ->assertSessionHas('error', fn ($message) => str_contains($message, $expected))
                ->assertSessionMissing(CouponService::SESSION_KEY);
        }
    }

    public function test_a_coupon_that_became_invalid_is_dropped_instead_of_breaking_the_order(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5]);

        $this->withSession($this->cart($product) + [CouponService::SESSION_KEY => 'GONE']);

        $this->post(route('checkout.process'), $this->checkoutData())
            ->assertRedirect()
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'n\'existe pas'));

        $this->assertSame(0, Order::count());
        $this->assertNull(session(CouponService::SESSION_KEY));
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_the_coupon_is_removable_before_paying(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5]);
        Coupon::factory()->create(['code' => 'PROMO10']);

        $this->withSession($this->cart($product));
        $this->post(route('checkout.coupon.apply'), ['coupon' => 'PROMO10']);

        $this->delete(route('checkout.coupon.remove'))
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionMissing(CouponService::SESSION_KEY);

        $html = $this->get(route('checkout.index'))->getContent();

        $this->assertStringNotContainsString('Réduction (PROMO10)', $html);
    }

    public function test_cancelling_an_order_gives_its_coupon_use_back(): void
    {
        Coupon::factory()->create(['code' => 'PROMO10', 'used_count' => 1]);
        $order = Order::factory()->create(['coupon_code' => 'PROMO10', 'discount' => 1000]);

        $this->assertTrue($order->releaseStock());
        $this->assertSame(0, Coupon::where('code', 'PROMO10')->value('used_count'));

        $this->assertTrue($order->reserveStock());
        $this->assertSame(1, Coupon::where('code', 'PROMO10')->value('used_count'));
    }
}
