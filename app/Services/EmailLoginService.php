<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\EmailOneTimePasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sign-in by a one-time code sent to the customer's e-mail address.
 *
 * Unknown addresses go through exactly the same steps as known ones (quotas, pending session, code
 * screen); only the e-mail itself is skipped. The form therefore cannot be used to find out which
 * addresses have an account.
 */
class EmailLoginService
{
    public const SENT = 'sent';

    public const COOLDOWN = 'cooldown';

    public const EMAIL_LIMIT = 'email_limit';

    public const IP_LIMIT = 'ip_limit';

    public const FAILED = 'failed';

    private const SESSION_KEY = 'email_login';

    public static function normalize(string $email): string
    {
        return Str::lower(trim($email));
    }

    public function findLoginableCustomer(string $email): ?User
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user || $user->role !== 'customer' || ! $user->is_active || $user->isAdmin()) {
            return null;
        }

        return $user;
    }

    public function sendCode(?User $user, string $email, Request $request): string
    {
        $hash = sha1($email);
        $cooldownKey = 'email-otp:cooldown:'.$hash;
        $emailKey = 'email-otp:send:email:'.$hash;
        $ipKey = 'email-otp:send:ip:'.$request->ip();

        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            return self::COOLDOWN;
        }

        if (RateLimiter::tooManyAttempts($emailKey, $this->emailLimit())) {
            return self::EMAIL_LIMIT;
        }

        if (RateLimiter::tooManyAttempts($ipKey, $this->ipLimit())) {
            return self::IP_LIMIT;
        }

        RateLimiter::hit($cooldownKey, (int) config('email-otp.cooldown_seconds'));
        RateLimiter::hit($emailKey, 3600);
        RateLimiter::hit($ipKey, 3600);

        if ($user) {
            try {
                $user->notify(new EmailOneTimePasswordNotification($user->createOneTimePassword()));
            } catch (Throwable $e) {
                $user->deleteAllOneTimePasswords();

                Log::warning('E-mail one-time password could not be sent', [
                    'user_id' => $user->id,
                    'exception' => $e::class,
                ]);

                return self::FAILED;
            }
        }

        $this->rememberPending($request, $user, $email);

        return self::SENT;
    }

    /** Seconds before this address may be sent another code (0 when it may be now). */
    public function cooldownRemaining(string $email): int
    {
        return RateLimiter::availableIn('email-otp:cooldown:'.sha1($email));
    }

    /** @return array{user: ?User, email: string}|null */
    public function pending(Request $request): ?array
    {
        $data = $request->session()->get(self::SESSION_KEY);

        if (! is_array($data) || ! isset($data['email'], $data['sent_at'])) {
            return null;
        }

        $ttl = (int) config('email-otp.pending_ttl_minutes') * 60;

        if (now()->timestamp - (int) $data['sent_at'] > $ttl) {
            $this->clearPending($request);

            return null;
        }

        return [
            'user' => isset($data['user_id']) ? User::find($data['user_id']) : null,
            'email' => (string) $data['email'],
        ];
    }

    public function clearPending(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    /** j***@gmail.com: enough for the customer to recognise the address, not enough to leak it. */
    public static function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return Str::substr($local, 0, 1).'***@'.$domain;
    }

    private function rememberPending(Request $request, ?User $user, string $email): void
    {
        $request->session()->put(self::SESSION_KEY, [
            'user_id' => $user?->id,
            'email' => $email,
            'sent_at' => now()->timestamp,
        ]);

        $request->session()->regenerate();
    }

    private function emailLimit(): int
    {
        return max(1, (int) config('email-otp.email_hourly_limit'));
    }

    private function ipLimit(): int
    {
        return max(1, (int) config('email-otp.ip_hourly_limit'));
    }
}
