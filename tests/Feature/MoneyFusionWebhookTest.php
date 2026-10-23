<?php

namespace Tests\Feature;

use App\Mail\OrderPaid;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;
use Tests\TestCase;

class MoneyFusionWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'tok_test_123';

    private function order(array $overrides = []): Order
    {
        return Order::factory()->moneyFusion(self::TOKEN)->create(array_merge([
            'total' => 23000,
            'customer_email' => 'awa@example.com',
        ], $overrides));
    }

    private function payment(Order $order, array $overrides = []): MoneyFusionPayment
    {
        return MoneyFusionPayment::create(array_merge([
            'token_pay' => self::TOKEN,
            'order_id' => $order->id,
            'nom_client' => 'Awa Koné',
            'numero_send' => '0701020304',
            'montant' => 23000,
            'statut' => 'pending',
        ], $overrides));
    }

    /** What MoneyFusion's "check payment" endpoint answers for the token. */
    private function gatewayAnswers(string $status, array $data = []): void
    {
        // Http::fake() stubs accumulate and the first match wins: start from a clean client each time
        Http::swap(new HttpFactory);

        Http::fake([
            'moneyfusion.test/paiementNotif/*' => Http::response([
                'statut' => true,
                'data' => array_merge([
                    'statut' => $status,
                    'Montant' => 23000,
                    'numeroTransaction' => 'TX-1',
                    'moyen' => 'orange_money',
                ], $data),
            ]),
        ]);
    }

    private function notify(array $body = ['tokenPay' => self::TOKEN]): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/moneyfusion/webhook', $body);
    }

    public function test_a_verified_paid_notification_marks_the_order_paid_and_sends_the_confirmation_email(): void
    {
        Mail::fake();
        $order = $this->order();
        $payment = $this->payment($order);
        $this->gatewayAnswers('paid');

        $this->notify()->assertOk()->assertJson(['status' => 'ok']);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);

        $payment->refresh();
        $this->assertSame('paid', $payment->statut);
        $this->assertSame('TX-1', $payment->numero_transaction);
        $this->assertSame('orange_money', $payment->moyen);
        $this->assertNotNull($payment->paid_at);

        Mail::assertSent(OrderPaid::class, fn ($mail) => $mail->hasTo('awa@example.com') && $mail->order->is($order));
        Mail::assertSentCount(1);
    }

    public function test_the_request_body_is_never_believed(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->payment($order);
        $this->gatewayAnswers('pending');

        // A payer who knows their own token forges a "paid" notification.
        $this->notify(['tokenPay' => self::TOKEN, 'statut' => 'paid', 'Montant' => 23000, 'event' => 'payin.session.completed'])
            ->assertOk();

        $order->refresh();
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pending', $order->status);
        $this->assertSame('pending', MoneyFusionPayment::first()->statut);
        Mail::assertNothingSent();
    }

    public function test_nothing_is_changed_when_the_gateway_cannot_be_reached(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->payment($order);

        Http::fake(['moneyfusion.test/*' => Http::response('boom', 500)]);

        $this->notify(['tokenPay' => self::TOKEN, 'statut' => 'paid'])->assertStatus(503);

        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('pending', MoneyFusionPayment::first()->statut);
        Mail::assertNothingSent();
    }

    public function test_nothing_is_changed_when_the_gateway_connection_fails(): void
    {
        $order = $this->order();
        $this->payment($order);

        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->notify(['tokenPay' => self::TOKEN, 'statut' => 'paid'])->assertStatus(503);

        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_a_paid_status_for_the_wrong_amount_does_not_credit_the_order(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->payment($order);
        $this->gatewayAnswers('paid', ['Montant' => 100]);

        $this->notify();

        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('pending', MoneyFusionPayment::first()->statut);
        Mail::assertNothingSent();
    }

    public function test_a_duplicate_notification_does_not_send_a_second_email(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->payment($order);
        $this->gatewayAnswers('paid');

        $this->notify()->assertOk();
        $this->notify()->assertOk();

        Mail::assertSentCount(1);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_failed_payment_cancels_the_order(): void
    {
        $order = $this->order();
        $this->payment($order);
        $this->gatewayAnswers('failure');

        $this->notify()->assertOk();

        $order->refresh();
        $this->assertSame('failed', $order->payment_status);
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('failed', MoneyFusionPayment::first()->statut);
    }

    public function test_a_cancelled_payment_cancels_the_order(): void
    {
        $order = $this->order();
        $this->payment($order);
        $this->gatewayAnswers('no paid');

        $this->notify()->assertOk();

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('failed', $order->payment_status);
        $this->assertSame('cancelled', MoneyFusionPayment::first()->statut);
    }

    public function test_a_still_pending_payment_leaves_the_order_alone(): void
    {
        $order = $this->order();
        $this->payment($order);
        $this->gatewayAnswers('pending');

        $this->notify()->assertOk();

        $order->refresh();
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pending', $order->status);
    }

    public function test_a_paid_order_is_not_downgraded_by_a_late_failure_notification(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->payment($order);

        $this->gatewayAnswers('paid');
        $this->notify()->assertOk();

        $this->gatewayAnswers('failure');
        $this->notify()->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('paid', MoneyFusionPayment::first()->statut);
    }

    public function test_the_legacy_token_field_is_still_accepted(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->payment($order);
        $this->gatewayAnswers('paid');

        $this->notify(['token' => self::TOKEN])->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_missing_token_is_a_bad_request(): void
    {
        $this->notify(['statut' => 'paid'])->assertStatus(400);
    }

    public function test_an_unknown_token_is_not_found_and_calls_nothing(): void
    {
        Http::fake();

        $this->notify(['tokenPay' => 'does-not-exist'])->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_a_retry_repairs_an_order_whose_update_was_lost(): void
    {
        Mail::fake();
        $order = $this->order();
        // The payment was recorded as paid, but the order update never happened.
        $this->payment($order, ['statut' => 'paid', 'paid_at' => now()]);

        $this->notify()->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('processing', $order->fresh()->status);
        Mail::assertSentCount(1);
    }

    public function test_a_mail_failure_does_not_fail_the_webhook_or_lose_the_payment(): void
    {
        $order = $this->order();
        $this->payment($order);
        $this->gatewayAnswers('paid');
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));

        $this->notify()->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_cancelled_order_is_not_pushed_back_to_processing_by_a_payment(): void
    {
        Mail::fake();
        $order = $this->order(['status' => 'cancelled']);
        $this->payment($order);
        $this->gatewayAnswers('paid');

        $this->notify()->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('cancelled', $order->status);
        Mail::assertNothingSent();
    }

    public function test_the_payer_return_page_verifies_with_the_gateway_before_marking_the_order_paid(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->payment($order);
        $this->gatewayAnswers('paid');

        $this->get('/payment/callback?token='.self::TOKEN)
            ->assertRedirect($order->fresh()->successUrl());

        $this->assertSame('paid', $order->fresh()->payment_status);
        Mail::assertSentCount(1);
    }

    public function test_a_forged_return_visit_does_not_mark_the_order_paid(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->payment($order);
        $this->gatewayAnswers('pending');

        $this->get('/payment/callback?token='.self::TOKEN.'&statut=paid')->assertRedirect($order->pendingUrl());

        $this->assertSame('pending', $order->fresh()->payment_status);
        Mail::assertNothingSent();
    }

    public function test_the_webhook_and_the_return_page_send_one_email_between_them(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->payment($order);
        $this->gatewayAnswers('paid');

        $this->get('/payment/callback?token='.self::TOKEN);
        $this->notify()->assertOk();

        Mail::assertSentCount(1);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_the_webhook_needs_no_csrf_token_or_login(): void
    {
        $this->gatewayAnswers('pending');
        $this->payment($this->order());

        $this->post('/api/moneyfusion/webhook', ['tokenPay' => self::TOKEN], ['Accept' => 'application/json'])->assertOk();
    }

    /** An order that reserved $quantity units (the product stock is already reduced, as after checkout). */
    private function reservedOrder(int $stockLeft, int $quantity, ?ProductVariant $variant = null, array $orderOverrides = []): array
    {
        $product = $variant?->product ?? Product::factory()->create(['stock' => $stockLeft]);
        $order = $this->order($orderOverrides);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $quantity,
        ]);

        return [$order, $product];
    }

    public function test_a_failed_payment_gives_the_stock_back(): void
    {
        [$order, $product] = $this->reservedOrder(3, 2);
        $this->payment($order);
        $this->gatewayAnswers('failure');

        $this->notify()->assertOk();

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertNotNull($order->fresh()->stock_released_at);
    }

    public function test_a_cancelled_payment_gives_the_stock_back(): void
    {
        [$order, $product] = $this->reservedOrder(3, 2);
        $this->payment($order);
        $this->gatewayAnswers('no paid');

        $this->notify()->assertOk();

        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_variant_stock_is_given_back_too(): void
    {
        $product = Product::factory()->create(['stock' => 3]);
        $variant = ProductVariant::factory()->for($product)->create(['stock' => 1]);
        [$order] = $this->reservedOrder(3, 2, $variant);
        $this->payment($order);
        $this->gatewayAnswers('failure');

        $this->notify()->assertOk();

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame(3, $variant->fresh()->stock);
    }

    public function test_stock_is_given_back_only_once_across_repeated_and_mixed_notifications(): void
    {
        [$order, $product] = $this->reservedOrder(3, 2);
        $this->payment($order);

        $this->gatewayAnswers('failure');
        $this->notify()->assertOk();
        $this->notify()->assertOk();

        // The gateway later reports the same session as cancelled
        $this->gatewayAnswers('no paid');
        $this->notify()->assertOk();

        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_a_pending_notification_does_not_touch_the_stock(): void
    {
        [$order, $product] = $this->reservedOrder(3, 2);
        $this->payment($order);
        $this->gatewayAnswers('pending');

        $this->notify()->assertOk();

        $this->assertSame(3, $product->fresh()->stock);
        $this->assertNull($order->fresh()->stock_released_at);
    }

    public function test_a_payment_that_succeeds_after_a_failure_revives_the_order_and_takes_the_stock_again(): void
    {
        Mail::fake();
        [$order, $product] = $this->reservedOrder(3, 2);
        $this->payment($order);

        $this->gatewayAnswers('failure');
        $this->notify()->assertOk();
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame('cancelled', $order->fresh()->status);

        $this->gatewayAnswers('paid');
        $this->notify()->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertNull($order->stock_released_at);
        $this->assertSame(3, $product->fresh()->stock);
        // One e-mail for the failure, then one confirmation when the payment finally succeeds
        Mail::assertSent(\App\Mail\PaymentFailed::class, 1);
        Mail::assertSent(OrderPaid::class, 1);
    }

    public function test_reviving_an_order_never_drives_the_stock_below_zero(): void
    {
        Mail::fake();
        [$order, $product] = $this->reservedOrder(0, 2);
        $this->payment($order);

        $this->gatewayAnswers('failure');
        $this->notify();
        // Someone else buys everything in the meantime
        $product->update(['stock' => 1]);

        $this->gatewayAnswers('paid');
        $this->notify()->assertOk();

        $this->assertSame(0, $product->fresh()->stock);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_payment_after_a_customer_cancellation_does_not_revive_the_order_or_touch_the_stock(): void
    {
        Mail::fake();
        [$order, $product] = $this->reservedOrder(3, 2, null, ['status' => 'cancelled']);
        $order->releaseStock();
        $this->payment($order);
        $this->gatewayAnswers('paid');

        $this->notify()->assertOk();

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame('cancelled', $order->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_a_paid_order_keeps_its_stock_when_a_late_failure_arrives(): void
    {
        Mail::fake();
        [$order, $product] = $this->reservedOrder(3, 2);
        $this->payment($order);

        $this->gatewayAnswers('paid');
        $this->notify();
        $this->gatewayAnswers('failure');
        $this->notify();

        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_the_payer_can_come_back_without_a_token_when_the_return_url_names_the_order(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->payment($order);
        $this->gatewayAnswers('paid');

        $this->get(route('payment.callback', ['order' => $order->id]))
            ->assertRedirect($order->fresh()->successUrl());

        $this->assertSame('paid', $order->fresh()->payment_status);
        Mail::assertSentCount(1);
    }

    public function test_returning_for_an_order_still_pending_at_the_gateway_changes_nothing(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->payment($order);
        $this->gatewayAnswers('pending');

        $this->get(route('payment.callback', ['order' => $order->id]))->assertRedirect($order->pendingUrl());

        $this->assertSame('pending', $order->fresh()->payment_status);
        Mail::assertNothingSent();
    }

    public function test_an_order_without_a_moneyfusion_payment_shows_the_missing_token_page(): void
    {
        $order = Order::factory()->create();

        $this->get(route('payment.callback', ['order' => $order->id]))
            ->assertOk()
            ->assertSee('Token de paiement manquant');
    }

    public function test_a_return_without_token_or_order_shows_the_missing_token_page(): void
    {
        $this->get(route('payment.callback'))->assertOk()->assertSee('Token de paiement manquant');
    }
}
