<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SmsOneTimePasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SmsOtpEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '0712345678';

    private const E164 = '+2250712345678';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    /** @return array<string, string> */
    private function credentials(User $user, string $phone = self::PHONE, string $password = 'password'): array
    {
        return [
            'email' => $user->email,
            'password' => $password,
            'phone' => $phone,
        ];
    }

    private function assertedCode(User $user): string
    {
        $code = null;

        Notification::assertSentTo($user, SmsOneTimePasswordNotification::class, function (SmsOneTimePasswordNotification $notification) use (&$code): bool {
            $code = $notification->oneTimePassword->password;

            return true;
        });

        return (string) $code;
    }

    public function test_a_wrong_password_is_refused_generically_without_any_sms(): void
    {
        $user = User::factory()->create();

        $this->from(route('login.sms.activate'))
            ->post(route('login.sms.activate.send'), $this->credentials($user, password: 'not-the-password'))
            ->assertRedirect(route('login.sms.activate'))
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
        $this->assertGuest();
    }

    public function test_an_unknown_email_is_refused_generically(): void
    {
        $this->from(route('login.sms.activate'))
            ->post(route('login.sms.activate.send'), [
                'email' => 'nobody@example.com',
                'password' => 'password',
                'phone' => self::PHONE,
            ])
            ->assertRedirect(route('login.sms.activate'))
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_an_admin_cannot_activate_sms_sign_in(): void
    {
        $admin = User::factory()->admin()->create();

        $this->from(route('login.sms.activate'))
            ->post(route('login.sms.activate.send'), $this->credentials($admin))
            ->assertRedirect(route('login.sms.activate'))
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_an_inactive_customer_cannot_activate_sms_sign_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->from(route('login.sms.activate'))
            ->post(route('login.sms.activate.send'), $this->credentials($user))
            ->assertRedirect(route('login.sms.activate'))
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_a_phone_number_already_used_by_another_account_is_refused(): void
    {
        User::factory()->smsEnabled(self::PHONE)->create();
        $user = User::factory()->create();

        $this->from(route('login.sms.activate'))
            ->post(route('login.sms.activate.send'), $this->credentials($user))
            ->assertRedirect(route('login.sms.activate'))
            ->assertSessionHasErrors('phone');

        Notification::assertNothingSent();
        $this->assertGuest();
    }

    public function test_a_customer_verifies_the_number_once_and_then_signs_in_with_it(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.sms.activate.send'), $this->credentials($user))
            ->assertRedirect(route('login.sms.code'));

        $code = $this->assertedCode($user);
        $pending = (array) $this->app['session']->get('sms_login');
        $this->assertTrue($pending['activation']);

        $this->withSession(['sms_login' => $pending])
            ->post(route('login.sms.code.verify'), ['code' => $code])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertSame(self::E164, $user->phone);
        $this->assertSame(self::E164, $user->phone_normalized);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertTrue($user->isSmsLoginable());
        $this->assertDatabaseCount('one_time_passwords', 0);

        $this->post(route('logout'));
        $this->assertGuest();

        $this->travel(61)->seconds();

        $this->post(route('login.sms.send'), ['phone' => self::PHONE])
            ->assertRedirect(route('login.sms.code'));

        Notification::assertSentToTimes($user, SmsOneTimePasswordNotification::class, 2);
    }

    public function test_the_activation_code_is_routed_to_the_typed_number_before_it_is_stored(): void
    {
        $user = User::factory()->create();
        $routed = null;

        $this->post(route('login.sms.activate.send'), $this->credentials($user))
            ->assertRedirect(route('login.sms.code'));

        Notification::assertSentTo($user, SmsOneTimePasswordNotification::class, function (SmsOneTimePasswordNotification $notification, array $channels, User $notifiable) use (&$routed): bool {
            $routed = $notifiable->routeNotificationFor('vonage', $notification);

            return true;
        });

        $this->assertSame(self::E164, $routed);
        $this->assertNull($user->fresh()->phone_normalized);
    }

    public function test_an_account_that_is_already_verified_cannot_activate_again(): void
    {
        $user = User::factory()->smsEnabled(self::PHONE)->create();

        $this->from(route('login.sms.activate'))
            ->post(route('login.sms.activate.send'), $this->credentials($user, '0500000001'))
            ->assertRedirect(route('login.sms.activate'))
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_changing_the_number_in_the_profile_resets_the_verification(): void
    {
        $user = User::factory()->smsEnabled(self::PHONE)->create();
        $user->sendOneTimePassword();

        $this->actingAs($user)
            ->from(route('account.profile'))
            ->put(route('account.profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => '0712345679',
            ])
            ->assertRedirect(route('account.profile'));

        $user->refresh();
        $this->assertSame('0712345679', $user->phone);
        $this->assertNull($user->phone_normalized);
        $this->assertNull($user->phone_verified_at);
        $this->assertFalse($user->isSmsLoginable());
        $this->assertDatabaseCount('one_time_passwords', 0);
    }

    public function test_reformatting_the_same_number_keeps_the_verification(): void
    {
        $user = User::factory()->smsEnabled(self::PHONE)->create();

        $this->actingAs($user)
            ->from(route('account.profile'))
            ->put(route('account.profile.update'), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => '07 12 34 56 78',
            ])
            ->assertRedirect(route('account.profile'));

        $user->refresh();
        $this->assertSame(self::E164, $user->phone_normalized);
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_the_notification_is_a_short_ascii_message_without_a_url(): void
    {
        $user = User::factory()->smsEnabled(self::PHONE)->create();
        $user->sendOneTimePassword();

        Notification::assertSentTo($user, SmsOneTimePasswordNotification::class, function (SmsOneTimePasswordNotification $notification) use ($user): bool {
            $message = $notification->toLog($user);

            $this->assertSame(1, preg_match('/^[\x20-\x7E]+$/', $message), "Not ASCII: {$message}");
            $this->assertLessThan(160, strlen($message));
            $this->assertStringContainsString($notification->oneTimePassword->password, $message);
            $this->assertStringNotContainsString('http', $message);
            $this->assertStringNotContainsString('+225', $message);
            $this->assertSame(self::E164, $user->routeNotificationForVonage($notification));

            return true;
        });
    }
}
