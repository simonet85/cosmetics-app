<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\OrderLabels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontOrderPagesTest extends TestCase
{
    use RefreshDatabase;

    private function order(array $attributes = []): Order
    {
        $order = Order::factory()->create(array_merge([
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'status' => 'pending',
            'total' => 12000,
            'customer_email' => 'awa@example.com',
            'shipping_address' => ['first_name' => 'Awa', 'last_name' => 'Koné', 'city' => 'Abidjan', 'quartier' => 'Cocody', 'phone' => '0701020304'],
        ], $attributes));

        $product = Product::factory()->create(['name' => 'Sérum Éclat']);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'Sérum Éclat', 'sku' => 'S1', 'quantity' => 2, 'price' => 6000, 'total' => 12000]);

        return $order;
    }

    public function test_states_are_written_in_french(): void
    {
        $this->assertSame('En préparation', OrderLabels::status('processing'));
        $this->assertSame('Payé', OrderLabels::payment('paid'));
        $this->assertSame('Remboursé', OrderLabels::payment('refunded'));
        $this->assertSame('autre', OrderLabels::status('autre'));
    }

    public function test_cash_on_delivery_confirmation_is_honest_about_what_happens(): void
    {
        $order = $this->order();

        $html = $this->get($order->successUrl())->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('Commande enregistrée', $html);
        $this->assertStringContainsString("12\u{00A0}000\u{00A0}FCFA en espèces", $html);
        $this->assertStringContainsString('Sérum Éclat', $html);
        $this->assertStringContainsString('En attente', $html);
        $this->assertStringNotContainsString('>Pending<', $html);
        // No e-mail is sent for a cash order, so none is promised; nor any processing delay
        $this->assertStringNotContainsString('e-mail de confirmation', $html);
        $this->assertStringNotContainsString('24 heures', $html);
        $this->assertStringContainsString('Gardez cette page', $html);
    }

    public function test_a_paid_order_says_the_confirmation_e_mail_was_sent(): void
    {
        $order = $this->order(['payment_method' => 'moneyfusion', 'payment_status' => 'paid', 'status' => 'processing']);

        $this->get($order->successUrl())->assertOk()
            ->assertSee('Paiement reçu, merci')
            ->assertSee('Un e-mail de confirmation a été envoyé à awa@example.com')
            ->assertSee('En préparation')->assertSee('Payé');
    }

    public function test_bank_transfer_does_not_invent_bank_details(): void
    {
        $order = $this->order(['payment_method' => 'bank_transfer']);

        $html = $this->get($order->successUrl())->assertOk()->getContent();

        $this->assertStringContainsString('dès réception de votre virement', $html);
        $this->assertStringContainsString(route('pages.contact'), $html);
        $this->assertStringNotContainsString('envoyés par email', $html);
    }

    public function test_a_cancelled_order_is_shown_as_cancelled(): void
    {
        $order = $this->order(['status' => 'cancelled', 'payment_status' => 'failed']);

        $this->get($order->successUrl())->assertOk()->assertSee('Commande annulée')->assertSee('Annulée');
    }

    public function test_the_confirmation_needs_the_signed_link_or_the_owner(): void
    {
        $owner = User::factory()->create();
        $order = $this->order(['user_id' => $owner->id]);

        $this->get(route('checkout.success', $order))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('checkout.success', $order))->assertForbidden();
        $this->actingAs($owner)->get(route('checkout.success', $order))->assertOk()->assertSee('Mes commandes');
    }

    public function test_the_pending_page_polls_through_data_attributes_not_inline_script(): void
    {
        $order = $this->order(['payment_method' => 'moneyfusion']);

        $html = $this->get($order->pendingUrl())->assertOk()->getContent();

        $this->assertStringContainsString('data-payment-status', $html);
        $this->assertStringContainsString('data-status-url="'.str_replace('&', '&amp;', $order->paymentStatusUrl()).'"', $html);
        $this->assertStringContainsString('id="check-now"', $html);
        $this->assertStringNotContainsString('const statusUrl', $html);
    }

    public function test_gateway_result_pages_use_the_storefront_layout(): void
    {
        $order = $this->order(['payment_method' => 'moneyfusion']);

        foreach (['success' => 'Paiement réussi', 'failed' => 'Échec du paiement', 'pending' => 'Paiement en attente'] as $view => $title) {
            $html = view("moneyfusion.{$view}", ['order' => $order, 'payment' => null])->render();
            $this->assertStringContainsString($title, $html);
            $this->assertStringContainsString('id="content"', $html);
            $this->assertStringContainsString($order->order_number, $html);
        }

        $this->assertStringContainsString('Token manquant', view('moneyfusion.error', ['message' => 'Token manquant'])->render());
    }
}
