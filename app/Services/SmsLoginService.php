<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class SmsLoginService
{
    public const SENT = 'sent';

    public const COOLDOWN = 'cooldown';

    public const PHONE_LIMIT = 'phone_limit';

    public const IP_LIMIT = 'ip_limit';

    public const FAILED = 'failed';

    private const SESSION_KEY = 'sms_login';

    public function findLoginableCustomer(string $phone): ?User
    {
        $user = User::query()
            ->where('phone_normalized', $phone)
            ->where('role', 'customer')
            ->where('is_active', true)
            ->whereNotNull('phone_verified_at')
            ->first();

        return $user?->isSmsLoginable() ? $user : null;
    }

    public function findActivationCandidate(string $email, string $password): ?User
    {
        if (! Auth::validate(['email' => $email, 'password' => $password])) {
            return null;
        }

        $user = User::where('email', $email)->first();

        return $user?->canActivateSmsLogin() ? $user : null;
    }

    public function phoneBelongsToAnotherUser(string $phone, int $userId): bool
    {
        return User::query()
            ->where('phone_normalized', $phone)
            ->whereKeyNot($userId)
            ->exists();
    }

    /** Unknown numbers still count against the IP quota, so the form cannot be used to probe accounts. */
    public function hitIpForUnknownNumber(Request $request): string
    {
        $key = 'sms-otp:send:ip:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, $this->ipLimit())) {
            return self::IP_LIMIT;
        }

        RateLimiter::hit($key, 3600);

        return self::SENT;
    }

    public function sendCode(User $user, string $phone, Request $request, bool $activation): string
    {
        $cooldownKey = 'sms-otp:cooldown:'.$phone;
        $phoneKey = 'sms-otp:send:phone:'.$phone;
        $ipKey = 'sms-otp:send:ip:'.$request->ip();

        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            return self::COOLDOWN;
        }

        if (RateLimiter::tooManyAttempts($phoneKey, $this->phoneLimit())) {
            return self::PHONE_LIMIT;
        }

        if (RateLimiter::tooManyAttempts($ipKey, $this->ipLimit())) {
            return self::IP_LIMIT;
        }

        RateLimiter::hit($cooldownKey, (int) config('sms-otp.cooldown_seconds'));
        RateLimiter::hit($phoneKey, 3600);
        RateLimiter::hit($ipKey, 3600);

        // During activation the number is not stored yet: route this notification to the number the
        // customer typed, without persisting it until the code is verified. VonageChannel reads
        // routeNotificationForVonage(), which is phone_normalized.
        $user->phone_normalized = $phone;

        try {
            $user->sendOneTimePassword();
        } catch (Throwable $e) {
            $user->deleteAllOneTimePasswords();

            Log::warning('SMS one-time password could not be sent', [
                'user_id' => $user->id,
                'exception' => $e::class,
            ]);

            return self::FAILED;
        }

        $this->rememberPending($request, $user, $phone, $activation);

        return self::SENT;
    }

    /** @return array{user: User, phone: string, activation: bool}|null */
    public function pending(Request $request): ?array
    {
        $data = $request->session()->get(self::SESSION_KEY);

        if (! is_array($data) || ! isset($data['user_id'], $data['phone'], $data['sent_at'])) {
            return null;
        }

        $ttl = (int) config('sms-otp.pending_ttl_minutes') * 60;

        if (now()->timestamp - (int) $data['sent_at'] > $ttl) {
            $this->clearPending($request);

            return null;
        }

        $user = User::find($data['user_id']);

        if (! $user) {
            $this->clearPending($request);

            return null;
        }

        return [
            'user' => $user,
            'phone' => (string) $data['phone'],
            'activation' => (bool) ($data['activation'] ?? false),
        ];
    }

    public function clearPending(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    private function rememberPending(Request $request, User $user, string $phone, bool $activation): void
    {
        $request->session()->put(self::SESSION_KEY, [
            'user_id' => $user->id,
            'phone' => $phone,
            'activation' => $activation,
            'sent_at' => now()->timestamp,
        ]);

        $request->session()->regenerate();
    }

    private function phoneLimit(): int
    {
        return max(1, (int) config('sms-otp.phone_hourly_limit'));
    }

    private function ipLimit(): int
    {
        return max(1, (int) config('sms-otp.ip_hourly_limit'));
    }
}
