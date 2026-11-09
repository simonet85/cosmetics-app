<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function order(): Order
    {
        $order = Order::factory()->create([
            'subtotal' => 10000, 'tax' => 1500, 'shipping_cost' => 2000, 'total' => 13500,
            'customer_email' => 'awa@example.com',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => Product::factory()->create()->id,
            'variant_id' => null,
            'product_name' => 'Sérum Éclat',
            'sku' => 'S1',
            'quantity' => 2,
            'price' => 5000,
            'total' => 10000,
        ]);

        return $order;
    }

    public function test_the_invoice_is_for_admins_only(): void
    {
        $order = $this->order();

        $this->get(route('admin.orders.invoice', $order))->assertRedirect();
        $this->actingAs(User::factory()->create())->get(route('admin.orders.invoice', $order))->assertForbidden();
    }

    public function test_an_admin_can_download_the_invoice_as_a_pdf(): void
    {
        $order = $this->order();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.orders.invoice', $order))
            ->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('facture-'.$order->order_number.'.pdf', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_an_unknown_order_has_no_invoice(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.orders.invoice', 999999))
            ->assertNotFound();
    }
}
