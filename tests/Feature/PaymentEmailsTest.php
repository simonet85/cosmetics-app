<?php

namespace Tests\Feature;

use App\Mail\OrderPaid;
use App\Mail\PaymentFailed;
use App\Mail\PaymentPending;
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

class PaymentEmailsTest extends TestCase
{
    use RefreshDatabase;

    private function order(array $overrides = []): Order
    {
        return Order::factory()->moneyFusion('tok_mail')->create(array_merge([
            'total' => 23000,
            'customer_email' => 'awa@example.com',
            'order_number' => 'ORD-MAIL1',
        ], $overrides));
    }

    private function payment(Order $order, int $minutesOld = 5): MoneyFusionPayment
    {
        $payment = MoneyFusionPayment::create([
            'token_pay' => 'tok_mail',
            'order_id' => $order->id,
            'nom_client' => 'Awa Koné',
            'numero_send' => '0701020304',
            'montant' => 23000,
            'statut' => 'pending',
        ]);
        DB::table('moneyfusion_payments')->where('id', $payment->id)->update(['created_at' => now()->subMinutes($minutesOld)]);

        return $payment;
    }

    private function gateway(string $status): void
    {
        Http::swap(new HttpFactory);
        Http::fake(['moneyfusion.test/paiementNotif/*' => Http::response(['statut' => true, 'data' => ['statut' => $status, 'Montant' => 23000, 'moyen' => 'orange']])]);
    }

    private function notify()
    {
        return $this->postJson('/api/moneyfusion/webhook', ['tokenPay' => 'tok_mail']);
    }

    // ------------------------------------------------------------ 5.1 payment confirmed

    public function test_the_confirmation_email_carries_the_invoice_pdf(): void
    {
        $order = $this->order();
        $attachments = (new OrderPaid($order))->attachments();

        $this->assertCount(1, $attachments);
        $this->assertSame('invoice-ORD-MAIL1.pdf', $attachments[0]->as);
        $this->assertSame('application/pdf', $attachments[0]->mime);
    }

    public function test_a_confirmed_payment_sends_the_confirmation_but_not_the_failure_email(): void
    {
        Mail::fake();
        $this->payment($this->order());
        $this->gateway('paid');

        $this->notify()->assertOk();

        Mail::assertSent(OrderPaid::class, fn ($mail) => $mail->hasTo('awa@example.com'));
        Mail::assertNotSent(PaymentFailed::class);
        Mail::assertNotSent(PaymentPending::class);
    }

    // ------------------------------------------------------------ 5.2 payment failed

    public function test_a_failed_payment_emails_the_customer_with_a_retry_link(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->payment($order);
        $this->gateway('failure');

        $this->notify()->assertOk();

        Mail::assertSent(PaymentFailed::class, function (PaymentFailed $mail) use ($order) {
            return $mail->hasTo('awa@example.com') && $mail->order->is($order) && $mail->reason === 'failed';
        });
        Mail::assertSentCount(1);
    }

    public function test_a_cancelled_payment_emails_the_customer_with_the_cancelled_reason(): void
    {
        Mail::fake();
        $this->payment($this->order());
        $this->gateway('no paid');

        $this->notify()->assertOk();

        Mail::assertSent(PaymentFailed::class, fn ($mail) => $mail->reason === 'cancelled');
    }

    public function test_repeated_failure_notifications_send_one_email(): void
    {
        Mail::fake();
        $this->payment($this->order());

        $this->gateway('failure');
        $this->notify();
        $this->notify();
        $this->gateway('no paid');
        $this->notify();

        Mail::assertSent(PaymentFailed::class, 1);
    }

    public function test_a_late_failure_after_payment_sends_nothing(): void
    {
        Mail::fake();
        $this->payment($this->order());

        $this->gateway('paid');
        $this->notify();
        $this->gateway('failure');
        $this->notify();

        Mail::assertNotSent(PaymentFailed::class);
        Mail::assertSent(OrderPaid::class, 1);
    }

    public function test_the_failure_email_content(): void
    {
        $order = $this->order(['shipping_address' => ['first_name' => 'Awa', 'last_name' => 'Koné', 'city' => 'Abidjan', 'quartier' => 'Cocody', 'phone' => '0701020304']]);

        $failed = (new PaymentFailed($order, 'failed'))->render();
        $this->assertStringContainsString('ORD-MAIL1', $failed);
        $this->assertStringContainsString("23\u{00A0}000\u{00A0}FCFA", $failed);
        $this->assertStringContainsString('Bonjour Awa', $failed);
        $this->assertStringContainsString('Paiement refusé', $failed);
        $this->assertStringContainsString('Aucun montant ne vous a été débité', $failed);
        $this->assertStringContainsString('href="'.route('checkout.index').'"', $failed);
        $this->assertStringContainsString('Réessayer le paiement', $failed);
        $this->assertStringContainsString('ne refaites pas de paiement', $failed);

        $this->assertStringContainsString('Paiement annulé', (new PaymentFailed($order, 'cancelled'))->render());
        $this->assertStringContainsString('paiement non reçu', (new PaymentFailed($order, 'expired'))->render());
    }

    public function test_the_failure_email_subject_names_the_order(): void
    {
        $mail = new PaymentFailed($this->order());

        $mail->assertHasSubject('Votre paiement n\'a pas abouti - commande ORD-MAIL1 - Glowing Cosmetics');
    }

    public function test_a_mail_failure_does_not_break_the_failure_handling(): void
    {
        $order = $this->order();
        $this->payment($order);
        $this->gateway('failure');
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));

        $this->notify()->assertOk();

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    // ------------------------------------------------------------ 5.3 pending reminder

    public function test_the_command_reminds_the_customer_once_when_the_payment_is_still_pending(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->payment($order, 90);
        $this->gateway('pending');

        $this->artisan('moneyfusion:check-pending', ['--pause' => 0])->assertSuccessful();
        $this->artisan('moneyfusion:check-pending', ['--pause' => 0])->assertSuccessful();

        Mail::assertSent(PaymentPending::class, fn ($mail) => $mail->hasTo('awa@example.com') && $mail->order->is($order));
        Mail::assertSent(PaymentPending::class, 1);
        $this->assertNotNull($order->fresh()->payment_reminder_sent_at);
    }

    public function test_no_reminder_before_the_delay(): void
    {
        Mail::fake();
        $this->payment($this->order(), 40);
        $this->gateway('pending');

        $this->artisan('moneyfusion:check-pending', ['--pause' => 0, '--age' => 0])->assertSuccessful();

        Mail::assertNotSent(PaymentPending::class);
    }

    public function test_the_reminder_can_be_switched_off(): void
    {
        Mail::fake();
        $this->payment($this->order(), 600);
        $this->gateway('pending');

        $this->artisan('moneyfusion:check-pending', ['--pause' => 0, '--remind-after' => 0])->assertSuccessful();

        Mail::assertNotSent(PaymentPending::class);
    }

    public function test_no_reminder_when_the_payment_turns_out_paid_or_failed(): void
    {
        Mail::fake();
        $this->payment($this->order(), 90);

        $this->gateway('paid');
        $this->artisan('moneyfusion:check-pending', ['--pause' => 0])->assertSuccessful();

        Mail::assertNotSent(PaymentPending::class);
        Mail::assertSent(OrderPaid::class, 1);
    }

    public function test_no_reminder_for_an_order_that_is_not_waiting_any_more(): void
    {
        Mail::fake();
        $this->payment($this->order(['status' => 'cancelled']), 90);
        $this->gateway('pending');

        $this->artisan('moneyfusion:check-pending', ['--pause' => 0])->assertSuccessful();

        Mail::assertNotSent(PaymentPending::class);
    }

    public function test_the_reminder_links_to_the_pending_page_and_states_the_time_left(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->payment($order, 120);
        $this->gateway('pending');

        $this->artisan('moneyfusion:check-pending', ['--pause' => 0])->assertSuccessful();

        Mail::assertSent(PaymentPending::class, function (PaymentPending $mail) {
            $html = $mail->render();

            return $mail->expiresInHours === 22
                && str_contains($html, 'ORD-MAIL1')
                && str_contains($html, "23\u{00A0}000\u{00A0}FCFA")
                && str_contains($html, 'Vérifier mon paiement')
                && str_contains($html, '/checkout/pending/')
                && str_contains($html, 'signature=')
                && str_contains($html, 'environ 22 heure(s)');
        });
    }

    public function test_the_reminder_omits_the_deadline_when_expiry_is_off(): void
    {
        $order = $this->order();

        $html = (new PaymentPending($order, null))->render();

        $this->assertStringNotContainsString('annulée automatiquement', $html);
    }

    public function test_the_reminder_subject_names_the_order(): void
    {
        (new PaymentPending($this->order()))->assertHasSubject('Paiement en attente de confirmation - commande ORD-MAIL1 - Glowing Cosmetic'.'s');
    }

    public function test_a_failed_reminder_mail_does_not_stop_the_run(): void
    {
        $order = $this->order();
        $this->payment($order, 90);
        $this->gateway('pending');
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));

        $this->artisan('moneyfusion:check-pending', ['--pause' => 0])->assertSuccessful();

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_an_expired_payment_emails_the_customer_that_the_order_was_cancelled(): void
    {
        Mail::fake();
        $product = Product::factory()->create(['stock' => 3]);
        $order = $this->order();
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2]);
        $this->payment($order, 1500);
        $this->gateway('pending');

        $this->artisan('moneyfusion:check-pending', ['--pause' => 0])->assertSuccessful();

        Mail::assertSent(PaymentFailed::class, fn ($mail) => $mail->reason === 'expired' && $mail->hasTo('awa@example.com'));
        Mail::assertNotSent(PaymentPending::class);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_the_report_counts_the_reminders(): void
    {
        Mail::fake();
        $this->payment($this->order(), 90);
        $this->gateway('pending');

        $this->artisan('moneyfusion:check-pending', ['--pause' => 0])
            ->expectsTable(['Result', 'Payments'], [
                ['checked', 1], ['paid', 0], ['failed', 0], ['cancelled', 0],
                ['still pending', 1], ['reminded', 1], ['expired', 0], ['unverified', 0], ['errors', 0],
            ])
            ->assertSuccessful();
    }
}
