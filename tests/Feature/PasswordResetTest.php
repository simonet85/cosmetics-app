<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_the_forgot_password_form(): void
    {
        $this->get('/login')->assertOk()->assertSee(route('password.request'), false);
        $this->get(route('password.request'))->assertOk()->assertSee('Adresse e-mail');
    }

    public function test_a_known_address_receives_a_french_reset_mail(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail->subject === 'Réinitialisation de votre mot de passe'
                && str_contains($mail->actionUrl, '/reset-password/');
        });
    }

    public function test_an_unknown_address_gets_the_same_answer_and_no_mail(): void
    {
        Notification::fake();

        $known = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

        $known->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_the_password_can_be_changed_with_a_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()->assertSee($token, false);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $user->fresh()->password));
    }

    public function test_an_invalid_token_changes_nothing(): void
    {
        $user = User::factory()->create();
        $before = $user->password;

        $this->post(route('password.update'), [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertSessionHasErrors('email');

        $this->assertSame($before, $user->fresh()->password);
    }

    public function test_a_weak_or_unconfirmed_password_is_refused(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token, 'email' => $user->email,
            'password' => 'court', 'password_confirmation' => 'court',
        ])->assertSessionHasErrors('password');

        $this->post(route('password.update'), [
            'token' => $token, 'email' => $user->email,
            'password' => 'nouveau-mot-de-passe', 'password_confirmation' => 'autre-chose',
        ])->assertSessionHasErrors('password');
    }

    public function test_a_token_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $payload = ['token' => $token, 'email' => $user->email, 'password' => 'premier-mot-de-passe', 'password_confirmation' => 'premier-mot-de-passe'];

        $this->post(route('password.update'), $payload)->assertRedirect(route('login'));
        $this->post(route('password.update'), array_merge($payload, ['password' => 'second-mot-de-passe', 'password_confirmation' => 'second-mot-de-passe']))
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('premier-mot-de-passe', $user->fresh()->password));
    }
}
