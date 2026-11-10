<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SmsOneTimePasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SmsOtpLoginTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '0712345678';

    private const E164 = '+2250712345678';

    private const SESSION_KEY = 'sms_login';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function smsCustomer(string $phone = self::PHONE): User
    {
        return User::factory()->smsEnabled($phone)->create();
    }

    /** @return array<int, string> */
    private function codesFor(User $user): array
    {
        return Notification::sent($user, SmsOneTimePasswordNotification::class)
            ->map(fn (SmsOneTimePasswordNotification $notification): string => $notification->oneTimePassword->password)
            ->values()
            ->all();
    }

    private function codeFor(User $user): string
    {
        $codes = $this->codesFor($user);

        $this->assertNotEmpty($codes, 'No SMS code was sent.');

        return end($codes);
    }

    /** @return array<string, mixed> */
    private function pending(User $user, string $phone = self::E164, bool $activation = false): array
    {
        return [self::SESSION_KEY => [
            'user_id' => $user->id,
            'phone' => $phone,
            'activation' => $activation,
            'sent_at' => now()->timestamp,
        ]];
    }

    private function pendingFromSession(): array
    {
        return (array) $this->app['session']->get(self::SESSION_KEY);
    }

    private function wrongCode(string $code): string
    {
        return $code === '000000' ? '111111' : '000000';
    }

    public function test_expired_codes_are_pruned_daily_by_the_scheduler(): void
    {
        $this->artisan('schedule:list');

        $event = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->firstWhere('description', 'prune-one-time-passwords');

        $this->assertNotNull($event);
        $this->assertSame('30 3 * * *', $event->expression);
    }

    public function test_the_login_page_offers_sms_sign_in(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Se connecter par SMS')
            ->assertSee(route('login.sms'), false);
    }

    public function test_the_phone_page_is_a_single_heading_form_with_a_phone_field(): void
    {
        $html = $this->get(route('login.sms'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('autocomplete="tel"', $html);
        $this->assertStringContainsString('noindex', $html);
        $this->assertStringContainsString('07 12 34 56 78', $html);
    }

    public function test_a_verified_customer_receives_a_code_and_reaches_the_code_page(): void
    {
        $user = $this->smsCustomer();

        $this->post(route('login.sms.send'), ['phone' => self::PHONE])
            ->assertRedirect(route('login.sms.code'));

        Notification::assertSentToTimes($user, SmsOneTimePasswordNotification::class, 1);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->codeFor($user));

        $pending = $this->pendingFromSession();
        $this->assertSame($user->id, $pending['user_id']);
        $this->assertSame(self::E164, $pending['phone']);
        $this->assertFalse($pending['activation']);
        $this->assertDatabaseCount('one_time_passwords', 1);
    }

    #[DataProvider('equivalentFormats')]
    public function test_equivalent_input_formats_reach_the_same_customer(string $input): void
    {
        $user = $this->smsCustomer();

        $this->post(route('login.sms.send'), ['phone' => $input])
            ->assertRedirect(route('login.sms.code'));

        Notification::assertSentTo($user, SmsOneTimePasswordNotification::class);
        $this->assertSame(self::E164, $this->pendingFromSession()['phone']);
    }

    /** @return array<string, array{string}> */
    public static function equivalentFormats(): array
    {
        return [
            'spaces' => ['07 12 34 56 78'],
            'country code' => ['+225 07 12 34 56 78'],
            'international prefix' => ['00225 07 12 34 56 78'],
        ];
    }

    public function test_an_unknown_number_is_redirected_to_activation_without_any_sms(): void
    {
        $this->post(route('login.sms.send'), ['phone' => '0500000000'])
            ->assertRedirect(route('login.sms.activate'))
            ->assertSessionHas('status');

        Notification::assertNothingSent();
        $this->assertNull($this->app['session']->get(self::SESSION_KEY));
    }

    public function test_an_invalid_number_is_rejected_before_any_lookup(): void
    {
        $this->from(route('login.sms'))
            ->post(route('login.sms.send'), ['phone' => '+226 07 12 34 56 78'])
            ->assertRedirect(route('login.sms'))
            ->assertSessionHasErrors('phone');

        Notification::assertNothingSent();
    }

    public function test_an_admin_never_receives_a_code(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->forceFill([
            'phone' => self::PHONE,
            'phone_normalized' => self::E164,
            'phone_verified_at' => now(),
        ])->save();

        $this->post(route('login.sms.send'), ['phone' => self::PHONE])
            ->assertRedirect(route('login.sms.activate'));

        Notification::assertNothingSent();
    }

    public function test_an_inactive_customer_never_receives_a_code(): void
    {
        $user = User::factory()->smsEnabled(self::PHONE)->create(['is_active' => false]);

        $this->post(route('login.sms.send'), ['phone' => self::PHONE])
            ->assertRedirect(route('login.sms.activate'));

        Notification::assertNotSentTo($user, SmsOneTimePasswordNotification::class);
        Notification::assertNothingSent();
    }

    public function test_an_unverified_number_never_receives_a_code(): void
    {
        $user = User::factory()->create(['phone' => self::PHONE]);
        $user->forceFill(['phone_normalized' => self::E164])->save();

        $this->post(route('login.sms.send'), ['phone' => self::PHONE])
            ->assertRedirect(route('login.sms.activate'));

        Notification::assertNotSentTo($user, SmsOneTimePasswordNotification::class);
    }

    public function test_a_valid_code_signs_the_customer_in_and_clears_the_sms_session(): void
    {
        $user = $this->smsCustomer();
        $user->sendOneTimePassword();

        $this->withSession($this->pending($user))
            ->post(route('login.sms.code.verify'), ['code' => $this->codeFor($user)])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('one_time_passwords', 0);
        $this->assertNull($this->app['session']->get(self::SESSION_KEY));
    }

    public function test_the_intended_page_is_restored_after_sign_in(): void
    {
        $user = $this->smsCustomer();
        $user->sendOneTimePassword();

        $this->get(route('account.dashboard'))->assertRedirect(route('login'));

        $this->withSession($this->pending($user))
            ->post(route('login.sms.code.verify'), ['code' => $this->codeFor($user)])
            ->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_code_keeps_the_guest_out_and_the_code_usable(): void
    {
        $user = $this->smsCustomer();
        $user->sendOneTimePassword();
        $code = $this->codeFor($user);

        $this->withSession($this->pending($user))
            ->post(route('login.sms.code.verify'), ['code' => $this->wrongCode($code)])
            ->assertRedirect(route('login.sms.code'))
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertDatabaseCount('one_time_passwords', 1);
    }

    public function test_an_expired_code_is_rejected(): void
    {
        $user = $this->smsCustomer();
        $user->sendOneTimePassword();
        $code = $this->codeFor($user);

        $this->travel(6)->minutes();

        $this->withSession($this->pending($user))
            ->post(route('login.sms.code.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_a_code_used_from_another_user_agent_is_rejected(): void
    {
        $user = $this->smsCustomer();
        $user->sendOneTimePassword();
        $code = $this->codeFor($user);

        $this->withHeader('User-Agent', 'Another device')
            ->withSession($this->pending($user))
            ->post(route('login.sms.code.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertDatabaseCount('one_time_passwords', 1);
    }

    public function test_six_wrong_codes_within_a_minute_are_rate_limited(): void
    {
        $user = $this->smsCustomer();
        $user->sendOneTimePassword();
        $wrong = $this->wrongCode($this->codeFor($user));

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withSession($this->pending($user))
                ->post(route('login.sms.code.verify'), ['code' => $wrong])
                ->assertRedirect(route('login.sms.code'));
        }

        $this->withSession($this->pending($user))
            ->post(route('login.sms.code.verify'), ['code' => $wrong])
            ->assertStatus(429);

        $this->assertGuest();
    }

    public function test_a_fourth_send_within_an_hour_is_rate_limited(): void
    {
        config(['sms-otp.phone_hourly_limit' => 3]);
        $user = $this->smsCustomer();

        for ($send = 1; $send <= 3; $send++) {
            $this->post(route('login.sms.send'), ['phone' => self::PHONE])
                ->assertRedirect(route('login.sms.code'));

            $this->travel(61)->seconds();
        }

        $this->post(route('login.sms.send'), ['phone' => self::PHONE])->assertStatus(429);

        Notification::assertSentToTimes($user, SmsOneTimePasswordNotification::class, 3);
    }

    public function test_two_immediate_sends_are_cooled_down(): void
    {
        $user = $this->smsCustomer();

        $this->post(route('login.sms.send'), ['phone' => self::PHONE])
            ->assertRedirect(route('login.sms.code'));

        $this->post(route('login.sms.send'), ['phone' => self::PHONE])->assertStatus(429);

        Notification::assertSentToTimes($user, SmsOneTimePasswordNotification::class, 1);
    }

    public function test_the_ip_hourly_limit_blocks_distinct_numbers(): void
    {
        config(['sms-otp.ip_hourly_limit' => 2]);

        $first = $this->smsCustomer('0711111111');
        $second = $this->smsCustomer('0722222222');
        $third = $this->smsCustomer('0733333333');

        $this->post(route('login.sms.send'), ['phone' => '0711111111'])->assertRedirect(route('login.sms.code'));
        $this->post(route('login.sms.send'), ['phone' => '0722222222'])->assertRedirect(route('login.sms.code'));
        $this->post(route('login.sms.send'), ['phone' => '0733333333'])->assertStatus(429);

        Notification::assertSentTo($first, SmsOneTimePasswordNotification::class);
        Notification::assertSentTo($second, SmsOneTimePasswordNotification::class);
        Notification::assertNotSentTo($third, SmsOneTimePasswordNotification::class);
    }

    public function test_an_unknown_number_counts_against_the_ip_quota_only(): void
    {
        config(['sms-otp.ip_hourly_limit' => 1]);

        $this->post(route('login.sms.send'), ['phone' => '0500000000'])
            ->assertRedirect(route('login.sms.activate'));

        $this->assertTrue(RateLimiter::tooManyAttempts('sms-otp:send:ip:127.0.0.1', 1));
        $this->assertFalse(RateLimiter::tooManyAttempts('sms-otp:send:phone:+2250500000000', 1));
    }

    public function test_the_code_page_masks_the_number_and_helps_the_keyboard(): void
    {
        $user = $this->smsCustomer();
        $user->sendOneTimePassword();

        $html = $this->withSession($this->pending($user))
            ->get(route('login.sms.code'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('autocomplete="one-time-code"', $html);
        $this->assertStringContainsString('inputmode="numeric"', $html);
        $this->assertStringContainsString('pattern="[0-9]{6}"', $html);
        $this->assertStringContainsString('maxlength="6"', $html);
        $this->assertStringContainsString('+225 07 ** ** 78', $html);
        $this->assertStringNotContainsString('0712345678', $html);
    }

    public function test_the_code_page_requires_a_pending_session(): void
    {
        $this->get(route('login.sms.code'))
            ->assertRedirect(route('login.sms'))
            ->assertSessionHas('error');
    }

    public function test_an_expired_pending_session_sends_the_customer_back(): void
    {
        $user = $this->smsCustomer();
        $user->sendOneTimePassword();
        $code = $this->codeFor($user);

        $this->withSession(['sms_login' => [
            'user_id' => $user->id,
            'phone' => self::E164,
            'activation' => false,
            'sent_at' => now()->subMinutes(11)->timestamp,
        ]])
            ->post(route('login.sms.code.verify'), ['code' => $code])
            ->assertRedirect(route('login.sms'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_remember_me_sets_the_recaller_cookie(): void
    {
        $user = $this->smsCustomer();
        $user->sendOneTimePassword();

        $this->withSession($this->pending($user))
            ->post(route('login.sms.code.verify'), [
                'code' => $this->codeFor($user),
                'remember' => '1',
            ])
            ->assertRedirect('/')
            ->assertCookie(Auth::guard('web')->getRecallerName());

        $this->assertAuthenticatedAs($user);
    }

    public function test_resending_invalidates_the_previous_code(): void
    {
        $user = $this->smsCustomer();
        $user->sendOneTimePassword();
        $first = $this->codeFor($user);

        $this->travel(61)->seconds();

        $this->withSession($this->pending($user))
            ->post(route('login.sms.resend'))
            ->assertRedirect(route('login.sms.code'));

        Notification::assertSentToTimes($user, SmsOneTimePasswordNotification::class, 2);
        $second = $this->codeFor($user);
        $this->assertNotSame($first, $second);
        $this->assertDatabaseCount('one_time_passwords', 1);

        $this->withSession($this->pending($user))
            ->post(route('login.sms.code.verify'), ['code' => $first])
            ->assertSessionHasErrors('code');

        $this->withSession($this->pending($user))
            ->post(route('login.sms.code.verify'), ['code' => $second])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }
}
