<?php

namespace Tests\Feature;

use App\Mail\OrderPaid;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;
use Tests\TestCase;

class CheckPendingPaymentsCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Order, 1: MoneyFusionPayment, 2: Product} an order that reserved 2 units, with its pending payment */
    private function pendingPayment(string $token, int $minutesOld, int $stockLeft = 3): array
    {
        $product = Product::factory()->create(['stock' => $stockLeft]);
        $order = Order::factory()->moneyFusion($token)->create(['total' => 23000, 'customer_email' => 'awa@example.com']);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2]);

        $payment = MoneyFusionPayment::create([
            'token_pay' => $token,
            'order_id' => $order->id,
            'nom_client' => 'Awa Koné',
            'numero_send' => '0701020304',
            'montant' => 23000,
            'statut' => 'pending',
        ]);
        DB::table('moneyfusion_payments')->where('id', $payment->id)->update(['created_at' => now()->subMinutes($minutesOld)]);

        return [$order, $payment->fresh(), $product];
    }

    /** What the gateway answers per token: ['tok_a' => 'paid', 'tok_b' => 'failure', 'tok_c' => 500]. */
    private function gateway(array $answers): void
    {
        Http::swap(new HttpFactory);

        $stubs = [];
        foreach ($answers as $token => $status) {
            $stubs["moneyfusion.test/paiementNotif/{$token}"] = is_int($status)
                ? Http::response('error', $status)
                : Http::response(['statut' => true, 'data' => ['statut' => $status, 'Montant' => 23000, 'numeroTransaction' => 'TX', 'moyen' => 'orange']]);
        }

        Http::fake($stubs);
    }

    private function run_(array $options = []): \Illuminate\Testing\PendingCommand
    {
        return $this->artisan('moneyfusion:check-pending', array_merge(['--pause' => 0], $options));
    }

    public function test_a_payment_paid_at_the_gateway_marks_the_order_paid_and_sends_one_email(): void
    {
        Mail::fake();
        [$order, $payment] = $this->pendingPayment('tok_a', 45);
        $this->gateway(['tok_a' => 'paid']);

        $this->run_()->assertSuccessful();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('processing', $order->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->statut);
        Mail::assertSent(OrderPaid::class, 1);

        // A second run has nothing left to do
        $this->run_()->expectsOutputToContain('No pending payment to check.')->assertSuccessful();
        Mail::assertSent(OrderPaid::class, 1);
    }

    public function test_a_failed_payment_cancels_the_order_and_gives_the_stock_back(): void
    {
        [$order, $payment, $product] = $this->pendingPayment('tok_a', 45);
        $this->gateway(['tok_a' => 'failure']);

        $this->run_()->assertSuccessful();

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('failed', $order->payment_status);
        $this->assertSame('failed', $payment->fresh()->statut);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_a_cancelled_payment_cancels_the_order_and_gives_the_stock_back(): void
    {
        [$order, , $product] = $this->pendingPayment('tok_a', 45);
        $this->gateway(['tok_a' => 'no paid']);

        $this->run_()->assertSuccessful();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_a_payment_still_pending_and_recent_is_left_alone(): void
    {
        [$order, $payment, $product] = $this->pendingPayment('tok_a', 45);
        $this->gateway(['tok_a' => 'pending']);

        $this->run_()->assertSuccessful();

        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('pending', $payment->fresh()->statut);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_payments_younger_than_the_age_are_not_even_checked(): void
    {
        $this->pendingPayment('tok_a', 5);
        $this->gateway([]);

        $this->run_(['--age' => 30])->expectsOutputToContain('No pending payment to check.')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_payments_that_are_not_pending_are_ignored(): void
    {
        [, $payment] = $this->pendingPayment('tok_a', 90);
        $payment->update(['statut' => 'paid']);
        $this->gateway([]);

        $this->run_()->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_a_payment_abandoned_past_the_expiry_is_cancelled_and_its_stock_released(): void
    {
        [$order, $payment, $product] = $this->pendingPayment('tok_a', 1500);
        $this->gateway(['tok_a' => 'pending']);

        $this->run_()->assertSuccessful();

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('failed', $order->payment_status);
        $this->assertSame('cancelled', $payment->fresh()->statut);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_an_abandoned_payment_is_not_expired_before_the_delay(): void
    {
        [$order, , $product] = $this->pendingPayment('tok_a', 600);
        $this->gateway(['tok_a' => 'pending']);

        $this->run_(['--expire-after' => 1440])->assertSuccessful();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_expiry_can_be_switched_off(): void
    {
        [$order, , $product] = $this->pendingPayment('tok_a', 5000);
        $this->gateway(['tok_a' => 'pending']);

        $this->run_(['--expire-after' => 0])->assertSuccessful();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_a_payer_who_pays_after_the_order_expired_gets_the_order_back(): void
    {
        Mail::fake();
        [$order, , $product] = $this->pendingPayment('tok_a', 1500);

        $this->gateway(['tok_a' => 'pending']);
        $this->run_()->assertSuccessful();
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);

        // Later the payer completes the payment: the next check (or the webhook) revives the order.
        MoneyFusionPayment::where('token_pay', 'tok_a')->update(['statut' => 'pending']);
        $this->gateway(['tok_a' => 'paid']);
        $this->run_(['--age' => 0])->assertSuccessful();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame(3, $product->fresh()->stock);
        Mail::assertSent(OrderPaid::class, 1);
    }

    public function test_nothing_changes_when_the_gateway_is_unreachable_even_for_an_old_payment(): void
    {
        Mail::fake();
        [$order, $payment, $product] = $this->pendingPayment('tok_a', 5000);
        $this->gateway(['tok_a' => 500]);

        $this->run_()->assertSuccessful();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('pending', $payment->fresh()->statut);
        $this->assertSame(3, $product->fresh()->stock);
        Mail::assertNothingSent();
    }

    public function test_one_failing_check_does_not_stop_the_others(): void
    {
        Mail::fake();
        [$broken] = $this->pendingPayment('tok_a', 45);
        [$ok] = $this->pendingPayment('tok_b', 45);
        $this->gateway(['tok_a' => 500, 'tok_b' => 'paid']);

        $this->run_()->assertSuccessful();

        $this->assertSame('pending', $broken->fresh()->payment_status);
        $this->assertSame('paid', $ok->fresh()->payment_status);
    }

    public function test_a_paid_status_for_the_wrong_amount_is_not_credited(): void
    {
        Mail::fake();
        [$order] = $this->pendingPayment('tok_a', 45);
        Http::swap(new HttpFactory);
        Http::fake(['moneyfusion.test/paiementNotif/*' => Http::response(['statut' => true, 'data' => ['statut' => 'paid', 'Montant' => 100]])]);

        $this->run_()->assertSuccessful();

        $this->assertSame('pending', $order->fresh()->payment_status);
        Mail::assertNothingSent();
    }

    public function test_the_limit_caps_how_many_payments_are_checked_oldest_first(): void
    {
        [$oldest] = $this->pendingPayment('tok_a', 300);
        [$middle] = $this->pendingPayment('tok_b', 200);
        [$newest] = $this->pendingPayment('tok_c', 100);
        $this->gateway(['tok_a' => 'failure', 'tok_b' => 'failure', 'tok_c' => 'failure']);

        $this->run_(['--limit' => 2])->assertSuccessful();

        $this->assertSame('cancelled', $oldest->fresh()->status);
        $this->assertSame('cancelled', $middle->fresh()->status);
        $this->assertSame('pending', $newest->fresh()->status);
    }

    public function test_the_report_summarises_the_run(): void
    {
        Mail::fake();
        $this->pendingPayment('tok_a', 45);
        $this->pendingPayment('tok_b', 45);
        $this->pendingPayment('tok_c', 45);
        $this->gateway(['tok_a' => 'paid', 'tok_b' => 'failure', 'tok_c' => 'pending']);

        $this->run_()
            ->expectsOutputToContain('Checking 3 pending payment(s)')
            ->expectsTable(['Result', 'Payments'], [
                ['checked', 3], ['paid', 1], ['failed', 1], ['cancelled', 0],
                ['still pending', 1], ['reminded', 0], ['expired', 0], ['unverified', 0], ['errors', 0],
            ])
            ->assertSuccessful();
    }

    public function test_a_payment_without_an_order_is_still_checked_without_error(): void
    {
        $payment = MoneyFusionPayment::create(['token_pay' => 'tok_x', 'nom_client' => 'X', 'montant' => 23000, 'statut' => 'pending']);
        DB::table('moneyfusion_payments')->where('id', $payment->id)->update(['created_at' => now()->subMinutes(60)]);
        $this->gateway(['tok_x' => 'paid']);

        $this->run_()->assertSuccessful();

        $this->assertSame('paid', $payment->fresh()->statut);
    }

    public function test_the_command_is_scheduled_every_ten_minutes_and_nightly(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('moneyfusion:check-pending --age=10 --limit=100')
            ->expectsOutputToContain('moneyfusion:check-pending --age=1440 --limit=500')
            ->assertSuccessful();
    }
}
