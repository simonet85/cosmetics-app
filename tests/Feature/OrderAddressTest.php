<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderAddressTest extends TestCase
{
    use RefreshDatabase;

    private const ADDRESS = ['first_name' => 'Awa', 'last_name' => 'Koné', 'city' => 'Abidjan', 'quartier' => 'Cocody', 'phone' => '0701020304'];

    private function rawAddress(Order $order, string $column = 'shipping_address'): string
    {
        return DB::table('orders')->where('id', $order->id)->value($column);
    }

    public function test_an_array_is_stored_as_a_plain_json_object_and_read_back_as_an_array(): void
    {
        $order = Order::factory()->create(['shipping_address' => self::ADDRESS]);

        $this->assertEquals(self::ADDRESS, json_decode($this->rawAddress($order), true));
        $this->assertEquals(self::ADDRESS, $order->fresh()->shipping_address);
    }

    public function test_unicode_is_kept_readable_in_the_column(): void
    {
        $order = Order::factory()->create(['shipping_address' => self::ADDRESS]);

        $this->assertStringContainsString('Koné', $this->rawAddress($order));
    }

    public function test_a_legacy_double_encoded_row_is_still_read_as_an_array(): void
    {
        $order = Order::factory()->create();
        DB::table('orders')->where('id', $order->id)->update([
            'shipping_address' => json_encode(json_encode(self::ADDRESS)),
            'billing_address' => json_encode(json_encode(self::ADDRESS)),
        ]);

        $order = $order->fresh();
        $this->assertEquals(self::ADDRESS, $order->shipping_address);
        $this->assertEquals(self::ADDRESS, $order->billing_address);
    }

    public function test_a_json_string_assigned_to_the_attribute_is_normalised(): void
    {
        $order = Order::factory()->create(['shipping_address' => json_encode(self::ADDRESS)]);

        $this->assertEquals(self::ADDRESS, json_decode($this->rawAddress($order), true));
    }

    public function test_the_migration_rewrites_double_encoded_rows_and_leaves_clean_ones(): void
    {
        $legacy = Order::factory()->create();
        $clean = Order::factory()->create(['shipping_address' => self::ADDRESS, 'billing_address' => self::ADDRESS]);
        DB::table('orders')->where('id', $legacy->id)->update([
            'shipping_address' => json_encode(json_encode(self::ADDRESS)),
            'billing_address' => json_encode(json_encode(self::ADDRESS)),
        ]);
        $cleanBefore = $this->rawAddress($clean);

        (require database_path('migrations/2026_10_23_220000_normalise_double_encoded_order_addresses.php'))->up();

        $this->assertEquals(self::ADDRESS, json_decode($this->rawAddress($legacy), true));
        $this->assertEquals(self::ADDRESS, json_decode($this->rawAddress($legacy, 'billing_address'), true));
        $this->assertEquals(json_decode($cleanBefore, true), json_decode($this->rawAddress($clean), true));

        // Idempotent
        (require database_path('migrations/2026_10_23_220000_normalise_double_encoded_order_addresses.php'))->up();
        $this->assertEquals(self::ADDRESS, json_decode($this->rawAddress($legacy), true));
    }

    public function test_pages_and_emails_render_addresses_for_both_row_formats(): void
    {
        $clean = Order::factory()->create(['shipping_address' => self::ADDRESS, 'billing_address' => self::ADDRESS]);
        $legacy = Order::factory()->create();
        DB::table('orders')->where('id', $legacy->id)->update([
            'shipping_address' => json_encode(json_encode(self::ADDRESS)),
            'billing_address' => json_encode(json_encode(self::ADDRESS)),
        ]);

        foreach ([$clean, $legacy->fresh()] as $order) {
            $this->get($order->successUrl())->assertOk()->assertSee('Cocody');
            $this->assertStringContainsString('Cocody', view('emails.invoice', ['order' => $order])->render());
            $this->assertStringContainsString('Cocody', view('emails.order-paid', ['order' => $order])->render());
        }
    }
}
