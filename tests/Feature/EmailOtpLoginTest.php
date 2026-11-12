<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailOneTimePasswordNotification;
use App\Services\EmailLoginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class EmailOtpLoginTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'awa@example.com';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function codeFor(User $user): string
    {
        $sent = Notification::sent($user, EmailOneTimePasswordNotification::class);

        $this->assertNotEmpty($sent, 'No e-mail code was sent.');

        return $sent->last()->oneTimePassword->password;
    }

    private function requestCode(string $email = self::EMAIL): void
    {
        $this->post(route('login.email.send'), ['email' => $email])->assertRedirect(route('login.email.code'));
    }

    public function test_the_e_mail_form_and_the_login_page_link_are_shown(): void
    {
        $this->get(route('login.email'))->assertOk()->assertSee(__('Recevoir mon code'));
        $this->get(route('login'))->assertOk()->assertSee(route('login.email'), false);
    }

    public function test_a_customer_signs_in_with_the_code_sent_to_their_address(): void
    {
        $user = User::factory()->create(['email' => self::EMAIL]);

        $this->requestCode();
        $this->post(route('login.email.code.verify'), ['code' => $this->codeFor($user)])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_address_is_matched_whatever_its_case_and_spacing(): void
    {
        $user = User::factory()->create(['email' => self::EMAIL]);

        $this->requestCode('  Awa@Example.COM ');
        $this->post(route('login.email.code.verify'), ['code' => $this->codeFor($user)]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_code_is_refused_with_a_message_on_the_code_page(): void
    {
        User::factory()->create(['email' => self::EMAIL]);

        $this->requestCode();
        $this->from(route('login.email.code'))
            ->post(route('login.email.code.verify'), ['code' => '000000'])
            ->assertRedirect(route('login.email.code'))
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_a_code_is_single_use(): void
    {
        $user = User::factory()->create(['email' => self::EMAIL]);

        $this->requestCode();
        $code = $this->codeFor($user);
        $this->post(route('login.email.code.verify'), ['code' => $code]);
        Auth::logout();

        // The pending session was cleared by the first success: the code cannot even be tried again.
        $this->post(route('login.email.code.verify'), ['code' => $code])->assertRedirect(route('login.email'));
        $this->assertGuest();

        // And opening a new pending session does not bring the used code back to life.
        RateLimiter::clear('email-otp:cooldown:'.sha1(self::EMAIL));
        $this->requestCode();
        $this->post(route('login.email.code.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_an_unknown_address_looks_exactly_like_a_known_one(): void
    {
        $this->post(route('login.email.send'), ['email' => 'nobody@example.com'])
            ->assertRedirect(route('login.email.code'))
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();

        $this->get(route('login.email.code'))->assertOk()->assertSee('n***@example.com');

        $this->post(route('login.email.code.verify'), ['code' => '123456'])
            ->assertRedirect(route('login.email.code'))
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_an_inactive_customer_and_an_admin_cannot_use_the_e_mail_code(): void
    {
        $inactive = User::factory()->create(['email' => 'off@example.com', 'is_active' => false]);
        $admin = User::factory()->admin()->create(['email' => 'boss@example.com']);

        $this->requestCode('off@example.com');
        $this->post(route('login.email.send'), ['email' => 'boss@example.com'])->assertRedirect(route('login.email.code'));

        Notification::assertNotSentTo($inactive, EmailOneTimePasswordNotification::class);
        Notification::assertNotSentTo($admin, EmailOneTimePasswordNotification::class);
    }

    public function test_the_address_must_be_valid(): void
    {
        $this->from(route('login.email'))
            ->post(route('login.email.send'), ['email' => 'not-an-address'])
            ->assertRedirect(route('login.email'))
            ->assertSessionHasErrors('email');
    }

    public function test_resending_within_the_cooldown_stays_on_the_code_page_with_a_message_in_french(): void
    {
        $user = User::factory()->create(['email' => self::EMAIL]);

        $this->requestCode();
        $this->post(route('login.email.resend'))
            ->assertRedirect(route('login.email.code'))
            ->assertSessionHasErrors(['code' => __('Veuillez patienter une minute avant de demander un nouveau code.')]);

        Notification::assertSentToTimes($user, EmailOneTimePasswordNotification::class, 1);
    }

    public function test_resending_after_the_cooldown_sends_a_new_code(): void
    {
        $user = User::factory()->create(['email' => self::EMAIL]);

        $this->requestCode();
        RateLimiter::clear('email-otp:cooldown:'.sha1(self::EMAIL));
        $this->post(route('login.email.resend'))->assertRedirect(route('login.email.code'))->assertSessionHasNoErrors();

        Notification::assertSentToTimes($user, EmailOneTimePasswordNotification::class, 2);
    }

    public function test_the_hourly_limit_ends_on_a_message_not_on_a_bare_429_page(): void
    {
        config(['email-otp.email_hourly_limit' => 1]);
        User::factory()->create(['email' => self::EMAIL]);

        $this->requestCode();
        RateLimiter::clear('email-otp:cooldown:'.sha1(self::EMAIL));

        $this->from(route('login.email.code'))
            ->post(route('login.email.resend'))
            ->assertRedirect(route('login.email.code'))
            ->assertSessionHasErrors(['code' => __('Trop de demandes de code. Réessayez dans une heure.')]);
    }

    public function test_the_ip_limit_applies_to_unknown_addresses_too(): void
    {
        config(['email-otp.ip_hourly_limit' => 1]);

        $this->requestCode('one@example.com');
        $this->from(route('login.email'))
            ->post(route('login.email.send'), ['email' => 'two@example.com'])
            ->assertRedirect(route('login.email'))
            ->assertSessionHasErrors('email');
    }

    public function test_the_code_screen_expires_with_the_pending_session(): void
    {
        $this->get(route('login.email.code'))->assertRedirect(route('login.email'));

        $this->requestCode();
        $this->travel(config('email-otp.pending_ttl_minutes') + 1)->minutes();

        $this->get(route('login.email.code'))->assertRedirect(route('login.email'));
    }

    public function test_a_code_for_one_account_never_opens_another(): void
    {
        $alice = User::factory()->create(['email' => 'alice@example.com']);
        $bob = User::factory()->create(['email' => 'bob@example.com']);

        $this->requestCode('alice@example.com');
        $aliceCode = $this->codeFor($alice);

        $this->flushSession();
        $this->requestCode('bob@example.com');
        $this->post(route('login.email.code.verify'), ['code' => $aliceCode])->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertNotNull($bob);
    }

    public function test_the_mask_hides_the_local_part(): void
    {
        $this->assertSame('a***@example.com', EmailLoginService::mask('awa@example.com'));
    }

    public function test_a_signed_in_customer_is_sent_away_from_the_login_pages(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('login.email'))->assertRedirect('/');
        $this->get(route('login.email.code'))->assertRedirect('/');
    }
}
