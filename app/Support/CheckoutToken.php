<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Single-use ticket embedded in the checkout form.
 *
 * It is signed with the application key (no session needed, so it also survives an expired session) and
 * valid for a day. The controller remembers which order a token produced, so a double click, a reload of the
 * POST or a second tab sending the same form can never create a second order.
 */
class CheckoutToken
{
    private const LIFETIME = 86400;

    public static function make(): string
    {
        $id = Str::random(32);
        $issuedAt = now()->timestamp;

        return "{$id}.{$issuedAt}.".self::sign($id, $issuedAt);
    }

    public static function valid(mixed $token): bool
    {
        if (! is_string($token) || substr_count($token, '.') !== 2) {
            return false;
        }

        [$id, $issuedAt, $signature] = explode('.', $token);

        return ctype_digit($issuedAt)
            && (now()->timestamp - (int) $issuedAt) <= self::LIFETIME
            && hash_equals(self::sign($id, (int) $issuedAt), $signature);
    }

    private static function sign(string $id, int $issuedAt): string
    {
        return hash_hmac('sha256', "checkout|{$id}|{$issuedAt}", (string) config('app.key'));
    }
}
