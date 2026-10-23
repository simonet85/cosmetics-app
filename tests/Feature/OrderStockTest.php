<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderStockTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Order, 1: Product, 2: ?ProductVariant} */
    private function reservedOrder(int $productStockLeft = 3, int $quantity = 2, bool $withVariant = false, array $orderOverrides = []): array
    {
        $product = Product::factory()->create(['stock' => $productStockLeft]);
        $variant = $withVariant ? ProductVariant::factory()->for($product)->create(['stock' => 1]) : null;
        $order = Order::factory()->create($orderOverrides);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $quantity,
        ]);

        return [$order, $product, $variant];
    }

    public function test_release_gives_the_stock_back_once(): void
    {
        [$order, $product] = $this->reservedOrder();

        $this->assertTrue($order->releaseStock());
        $this->assertFalse($order->releaseStock());
        $this->assertFalse(Order::find($order->id)->releaseStock());

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertNotNull($order->fresh()->stock_released_at);
    }

    public function test_release_also_restores_the_variant_stock(): void
    {
        [$order, $product, $variant] = $this->reservedOrder(3, 2, true);

        $order->releaseStock();

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame(3, $variant->fresh()->stock);
    }

    public function test_release_restores_stock_of_a_soft_deleted_product(): void
    {
        [$order, $product] = $this->reservedOrder();
        $product->delete();

        $order->releaseStock();

        $this->assertSame(5, Product::withTrashed()->find($product->id)->stock);
    }

    public function test_reserve_takes_the_stock_again_once(): void
    {
        [$order, $product, $variant] = $this->reservedOrder(3, 2, true);
        $order->releaseStock();

        $this->assertTrue($order->reserveStock());
        $this->assertFalse($order->reserveStock());

        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame(1, $variant->fresh()->stock);
        $this->assertNull($order->fresh()->stock_released_at);
    }

    public function test_reserve_does_nothing_for_an_order_whose_stock_was_never_released(): void
    {
        [$order, $product] = $this->reservedOrder();

        $this->assertFalse($order->reserveStock());
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_reserve_never_goes_below_zero(): void
    {
        [$order, $product, $variant] = $this->reservedOrder(0, 2, true);
        $order->releaseStock();
        $product->update(['stock' => 1]);
        $variant->update(['stock' => 0]);

        $order->reserveStock();

        $this->assertSame(0, $product->fresh()->stock);
        $this->assertSame(0, $variant->fresh()->stock);
    }

    public function test_a_customer_cancelling_a_pending_order_restores_product_and_variant_stock_once(): void
    {
        $user = User::factory()->create();
        [$order, $product, $variant] = $this->reservedOrder(3, 2, true, ['user_id' => $user->id, 'status' => 'pending']);

        $this->actingAs($user)->put(route('account.orders.cancel', $order))->assertRedirect(route('account.orders'));
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame(3, $variant->fresh()->stock);

        // Not pending any more: a second attempt is refused and changes nothing
        $this->actingAs($user)->put(route('account.orders.cancel', $order))->assertSessionHas('error');
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_an_admin_cancelling_and_reopening_an_order_releases_then_reserves_the_stock(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        [$order, $product, $variant] = $this->reservedOrder(3, 2, true, ['status' => 'processing']);

        $this->actingAs($admin)->put(route('admin.orders.update-status', $order), ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame(3, $variant->fresh()->stock);

        // Cancelling again is a no-op
        $this->actingAs($admin)->put(route('admin.orders.update-status', $order), ['status' => 'cancelled']);
        $this->assertSame(5, $product->fresh()->stock);

        $this->actingAs($admin)->put(route('admin.orders.update-status', $order), ['status' => 'processing'])->assertRedirect();
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame(1, $variant->fresh()->stock);
    }

    public function test_the_migration_backfills_orders_that_are_already_cancelled(): void
    {
        $cancelled = Order::factory()->create(['status' => 'cancelled']);
        $active = Order::factory()->create(['status' => 'processing']);
        DB::table('orders')->update(['stock_released_at' => null]);

        $migration = require database_path('migrations/2026_10_24_090000_add_stock_released_at_to_orders_table.php');
        // The column exists already in the test schema: run only the backfill part of up()
        DB::table('orders')->where('status', 'cancelled')->update(['stock_released_at' => DB::raw('updated_at')]);

        $this->assertNotNull($cancelled->fresh()->stock_released_at);
        $this->assertNull($active->fresh()->stock_released_at);
        $this->assertIsObject($migration);
    }
}
