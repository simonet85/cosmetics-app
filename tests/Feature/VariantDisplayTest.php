<?php

namespace Tests\Feature;

use App\Mail\OrderPaid;
use App\Mail\OrderStatusUpdated;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function orderWithVariant(?User $user = null): array
    {
        $product = Product::factory()->create(['price' => 10000]);
        $variant = ProductVariant::factory()->for($product)->create([
            'size' => '100ml',
            'color_name' => 'Rose',
            'price_modifier' => 1000,
        ]);
        $order = Order::factory()->create(['user_id' => $user?->id ?? User::factory()]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
        ]);

        return [$order->fresh(), $variant];
    }

    public function test_label_combines_size_and_color(): void
    {
        $variant = ProductVariant::factory()->create(['size' => '100ml', 'color_name' => 'Rose']);

        $this->assertSame('100ml / Rose', $variant->label);
    }

    public function test_label_falls_back_to_color_then_sku(): void
    {
        $withColor = ProductVariant::factory()->create(['size' => null, 'color_name' => null, 'color' => 'Red']);
        $bare = ProductVariant::factory()->create(['size' => null, 'color_name' => null, 'color' => null, 'sku' => 'VAR-1']);

        $this->assertSame('Red', $withColor->label);
        $this->assertSame('VAR-1', $bare->label);
    }

    public function test_cart_page_shows_the_variant_label(): void
    {
        $product = Product::factory()->create(['price' => 10000]);
        $variant = ProductVariant::factory()->for($product)->create(['size' => '100ml']);

        $this->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $this->get(route('cart.index'))->assertOk()->assertSee('Variante:')->assertSee('100ml');
    }

    public function test_order_detail_page_shows_the_variant_label(): void
    {
        $user = User::factory()->create();
        [$order] = $this->orderWithVariant($user);

        $this->actingAs($user)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertSee('100ml / Rose');
    }

    public function test_checkout_success_page_shows_the_variant_label(): void
    {
        [$order] = $this->orderWithVariant();

        $this->actingAs($order->user)
            ->get(route('checkout.success', $order))
            ->assertOk()
            ->assertSee('100ml / Rose');
    }

    public function test_order_emails_render_the_variant_label(): void
    {
        [$order] = $this->orderWithVariant();

        (new OrderPaid($order))->assertSeeInHtml('100ml / Rose');
        (new OrderStatusUpdated($order, 'pending'))->assertSeeInHtml('100ml / Rose');
    }

    public function test_invoice_view_renders_the_variant_label(): void
    {
        [$order] = $this->orderWithVariant();

        $this->assertStringContainsString('100ml / Rose', view('emails.invoice', ['order' => $order])->render());
    }

    public function test_listing_shows_discount_price_with_list_price_struck_through(): void
    {
        $product = Product::factory()->discounted(8000)->create(['price' => 10000]);

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertSee("8\u{00A0}000")
            ->assertSee("10\u{00A0}000\u{00A0}FCFA")
            ->assertSee('<s class', false);
    }
}
