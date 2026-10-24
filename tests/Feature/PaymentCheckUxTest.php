<?php

namespace Tests\Feature;

use App\Mail\OrderPaid;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;
use Tests\TestCase;

class PaymentCheckUxTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'tok_ux';

    private function order(array $overrides = []): Order
    {
        return Order::factory()->moneyFusion(self::TOKEN)->create(array_merge(['total' => 23000, 'customer_email' => 'awa@example.com'], $overrides));
    }

    private function payment(Order $order, string $status = 'pending'): MoneyFusionPayment
    {
        return MoneyFusionPayment::create([
            'token_pay' => self::TOKEN,
            'order_id' => $order->id,
            'nom_client' => 'Awa Koné',
            'numero_send' => '0701020304',
            'montant' => 23000,
            'statut' => $status,
        ]);
    }

    /** @return array{0: Order, 1: MoneyFusionPayment} */
    private function pending(array $orderOverrides = []): array
    {
        $order = $this->order($orderOverrides);

        return [$order, $this->payment($order)];
    }

    private function gateway(string|int $answer): void
    {
        Http::swap(new HttpFactory);

        Http::fake(['moneyfusion.test/paiementNotif/*' => is_int($answer)
            ? Http::response('error', $answer)
            : Http::response(['statut' => true, 'data' => ['statut' => $answer, 'Montant' => 23000, 'numeroTransaction' => 'TX', 'moyen' => 'orange']])]);
    }

    // ---------------------------------------------------------------- status endpoint (4.1)

    public function test_a_guest_with_the_signed_link_sees_a_pending_payment(): void
    {
        [$order] = $this->pending();
        $this->gateway('pending');

        $this->getJson($order->paymentStatusUrl())
            ->assertOk()
            ->assertJson(['state' => 'pending', 'payment_status' => 'pending', 'redirect' => null])
            ->assertJsonStructure(['message', 'checked_at']);
    }

    public function test_a_confirmed_payment_returns_the_confirmation_link_and_updates_the_order_once(): void
    {
        Mail::fake();
        [$order] = $this->pending();
        $this->gateway('paid');

        $this->getJson($order->paymentStatusUrl())
            ->assertOk()
            ->assertJson(['state' => 'paid', 'payment_status' => 'paid', 'redirect' => $order->fresh()->successUrl()]);

        $this->assertSame('processing', $order->fresh()->status);
        Mail::assertSent(OrderPaid::class, 1);

        // Asking again changes nothing and does not call the gateway again
        $this->getJson($order->paymentStatusUrl())->assertJson(['state' => 'paid']);
        Mail::assertSent(OrderPaid::class, 1);
        Http::assertSentCount(1);
    }

    public function test_a_failed_payment_is_reported_as_failed_and_the_order_is_cancelled(): void
    {
        [$order] = $this->pending();
        $this->gateway('failure');

        $this->getJson($order->paymentStatusUrl())->assertOk()->assertJson(['state' => 'failed', 'redirect' => null]);

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_an_unreachable_gateway_is_reported_as_unavailable_not_as_a_failure(): void
    {
        [$order] = $this->pending();
        $this->gateway(500);

        $this->getJson($order->paymentStatusUrl())->assertOk()->assertJson(['state' => 'unavailable']);

        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_the_gateway_is_not_asked_more_than_once_in_the_throttle_window(): void
    {
        [$order] = $this->pending();
        $this->gateway('pending');

        $this->getJson($order->paymentStatusUrl())->assertOk();
        $this->getJson($order->paymentStatusUrl())->assertOk()->assertJson(['state' => 'pending']);
        $this->getJson($order->paymentStatusUrl())->assertOk();

        Http::assertSentCount(1);
    }

    public function test_the_owner_needs_no_signature(): void
    {
        $user = User::factory()->create();
        [$order] = $this->pending(['user_id' => $user->id]);
        $this->gateway('pending');

        $this->actingAs($user)->getJson(route('payment.status', $order))->assertOk()->assertJson(['state' => 'pending']);
    }

    public function test_nobody_else_can_read_the_status_of_an_order(): void
    {
        [$order] = $this->pending();
        $this->gateway('paid');

        $this->getJson(route('payment.status', $order))->assertForbidden();
        $this->actingAs(User::factory()->create())->getJson(route('payment.status', $order))->assertForbidden();

        // A signature made for another order does not open this one
        $other = $this->order(['order_number' => 'ORD-OTHER']);
        $signature = parse_url($other->paymentStatusUrl(), PHP_URL_QUERY);
        $this->getJson(route('payment.status', $order).'?'.$signature)->assertForbidden();

        Http::assertNothingSent();
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_a_non_moneyfusion_order_never_calls_the_gateway(): void
    {
        $order = Order::factory()->create(['payment_method' => 'cash_on_delivery']);
        Http::fake();

        $this->getJson($order->paymentStatusUrl())->assertOk()->assertJson(['state' => 'pending']);

        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------- pending page (4.6)

    public function test_the_pending_page_polls_the_signed_status_url(): void
    {
        [$order] = $this->pending();

        $response = $this->get($order->pendingUrl())->assertOk()
            ->assertSee('Paiement en cours')
            ->assertSee($order->order_number)
            ->assertSee('Vérifier maintenant');

        // The polling script (resources/js/storefront.js) reads a signed URL for the status endpoint from a data attribute
        $this->assertStringContainsString('data-status-url="'.url("/payment/status/{$order->id}"), $response->getContent());
        $this->assertStringContainsString('signature=', $response->getContent());
        $this->assertMatchesRegularExpression('/id="failed-box" class="[^"]*hidden/', $response->getContent());
    }

    public function test_the_pending_page_is_not_public(): void
    {
        [$order] = $this->pending();

        $this->get(route('checkout.pending', $order))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('checkout.pending', $order))->assertForbidden();
    }

    public function test_the_owner_can_open_the_pending_page_without_a_signature(): void
    {
        $user = User::factory()->create();
        [$order] = $this->pending(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('checkout.pending', $order))->assertOk();
    }

    public function test_an_order_already_paid_skips_the_pending_page(): void
    {
        $order = $this->order(['payment_status' => 'paid', 'status' => 'processing']);

        $this->get($order->pendingUrl())->assertRedirect($order->successUrl());
    }

    public function test_a_failed_order_shows_the_failure_box_instead_of_the_waiting_one(): void
    {
        $order = $this->order(['payment_status' => 'failed', 'status' => 'cancelled']);

        $content = $this->get($order->pendingUrl())->assertOk()->assertSee('Réessayer le paiement')->getContent();

        $this->assertMatchesRegularExpression('/id="pending-box" class="[^"]*hidden/', $content);
        $this->assertDoesNotMatchRegularExpression('/id="failed-box" class="[^"]*hidden/', $content);
    }

    public function test_coming_back_from_the_gateway_with_a_pending_payment_lands_on_the_pending_page(): void
    {
        [$order] = $this->pending();
        $this->gateway('pending');

        $this->get(route('payment.callback', ['order' => $order->id]))->assertRedirect($order->pendingUrl());
    }

    public function test_coming_back_with_a_confirmed_payment_still_goes_straight_to_the_confirmation(): void
    {
        Mail::fake();
        [$order] = $this->pending();
        $this->gateway('paid');

        $this->get(route('payment.callback', ['order' => $order->id]))->assertRedirect($order->fresh()->successUrl());
    }

    // ---------------------------------------------------------------- customer order page (4.2, 4.3)

    public function test_the_order_page_offers_the_check_button_while_a_moneyfusion_payment_is_pending(): void
    {
        $user = User::factory()->create();
        [$order] = $this->pending(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertSee('Vérifier maintenant')
            ->assertSee('id="payment-check-button"', false)
            ->assertSee('data-payment-poll', false)
            ->assertSee('data-status-url="'.$order->paymentStatusUrl(), false);
    }

    public function test_the_order_page_has_no_check_button_when_there_is_nothing_to_check(): void
    {
        $user = User::factory()->create();

        $paid = $this->order(['user_id' => $user->id, 'payment_status' => 'paid', 'status' => 'processing']);
        $cancelled = $this->order(['user_id' => $user->id, 'order_number' => 'ORD-CAN', 'payment_status' => 'failed', 'status' => 'cancelled']);
        $cod = Order::factory()->create(['user_id' => $user->id, 'payment_method' => 'cash_on_delivery']);

        foreach ([$paid, $cancelled, $cod] as $order) {
            $this->actingAs($user)->get(route('account.orders.show', $order))
                ->assertOk()
                ->assertDontSee('payment-check-button', false);
        }
    }

    // ---------------------------------------------------------------- admin (4.4, 4.5)

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_only_admins_can_check_a_payment(): void
    {
        [$order] = $this->pending();
        $this->gateway('paid');

        $this->post(route('admin.orders.check-payment', $order))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->post(route('admin.orders.check-payment', $order))->assertForbidden();

        $this->assertSame('pending', $order->fresh()->payment_status);
        Http::assertNothingSent();
    }

    public function test_an_admin_check_that_finds_the_payment_confirmed_marks_the_order_paid(): void
    {
        Mail::fake();
        [$order] = $this->pending();
        $this->gateway('paid');

        $this->actingAs($this->admin())->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.check-payment', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'marquée payée'));

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        Mail::assertSent(OrderPaid::class, 1);
    }

    public function test_an_admin_check_that_finds_a_failed_payment_cancels_the_order_and_releases_the_stock(): void
    {
        $product = Product::factory()->create(['stock' => 3]);
        [$order] = $this->pending();
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2]);
        $this->gateway('failure');

        $this->actingAs($this->admin())->post(route('admin.orders.check-payment', $order))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'a échoué'));

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_an_admin_check_on_a_still_pending_payment_changes_nothing(): void
    {
        [$order] = $this->pending();
        $this->gateway('pending');

        $this->actingAs($this->admin())->post(route('admin.orders.check-payment', $order))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'toujours en attente'));

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_an_admin_check_reports_an_unreachable_gateway_as_an_error(): void
    {
        [$order] = $this->pending();
        $this->gateway(500);

        $this->actingAs($this->admin())->post(route('admin.orders.check-payment', $order))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Impossible de joindre MoneyFusion'));

        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_an_admin_check_is_not_throttled(): void
    {
        [$order] = $this->pending();
        $this->gateway('pending');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.orders.check-payment', $order));
        $this->actingAs($admin)->post(route('admin.orders.check-payment', $order));

        Http::assertSentCount(2);
    }

    public function test_an_admin_check_can_revive_an_order_cancelled_for_a_failed_payment(): void
    {
        Mail::fake();
        $product = Product::factory()->create(['stock' => 3]);
        [$order, $payment] = $this->pending();
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2]);
        $admin = $this->admin();

        $this->gateway('failure');
        $this->actingAs($admin)->post(route('admin.orders.check-payment', $order));
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);

        // The payer completed it after all
        $this->gateway('paid');
        $this->actingAs($admin)->post(route('admin.orders.check-payment', $order));

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_an_admin_check_on_an_order_without_a_moneyfusion_payment_says_so(): void
    {
        $cod = Order::factory()->create(['payment_method' => 'cash_on_delivery']);
        $noPayment = $this->order(['order_number' => 'ORD-NOPAY']);
        Http::fake();
        $admin = $this->admin();

        foreach ([$cod, $noPayment] as $order) {
            $this->actingAs($admin)->post(route('admin.orders.check-payment', $order))
                ->assertSessionHas('error', fn ($m) => str_contains($m, 'Aucun paiement MoneyFusion'));
        }

        Http::assertNothingSent();
    }

    public function test_the_admin_order_page_shows_the_payment_details_and_the_check_button(): void
    {
        [$order, $payment] = $this->pending();

        $this->actingAs($this->admin())->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee(self::TOKEN)
            ->assertSee('Jeton MoneyFusion')
            ->assertSee($payment->created_at->format('d/m/Y H:i'))
            ->assertSee('Vérifier le paiement')
            ->assertSee(route('admin.orders.check-payment', $order), false);
    }

    public function test_the_admin_order_page_has_no_check_button_for_a_paid_or_non_moneyfusion_order(): void
    {
        $paid = $this->order(['payment_status' => 'paid', 'status' => 'processing']);
        $this->payment($paid, 'paid');
        $cod = Order::factory()->create(['payment_method' => 'cash_on_delivery']);
        $admin = $this->admin();

        foreach ([$paid, $cod] as $order) {
            $this->actingAs($admin)->get(route('admin.orders.show', $order))
                ->assertOk()
                ->assertDontSee('Vérifier le paiement');
        }
    }
}
