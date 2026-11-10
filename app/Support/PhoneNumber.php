<?php

namespace App\Support;

/**
 * Ivorian mobile numbers. The shop only texts +225 numbers, so every value is
 * reduced to E.164 before it is stored or looked up.
 */
final class PhoneNumber
{
    public const COUNTRY_CODE = '+225';

    /** Mobile prefixes are 01, 05 and 07; the local number is ten digits. */
    private const MOBILE_PATTERN = '/^(?:225|00225)?(0[157][0-9]{8})$/';

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || substr_count($value, '+') > 1) {
            return null;
        }

        if (str_contains($value, '+') && ! str_starts_with($value, '+')) {
            return null;
        }

        if (preg_match('/[^+0-9\s.\-()\/\x{00A0}\x{202F}]/u', $value) === 1) {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $value);

        if (! is_string($digits) || preg_match(self::MOBILE_PATTERN, $digits, $matches) !== 1) {
            return null;
        }

        return self::COUNTRY_CODE.$matches[1];
    }

    public static function mask(?string $canonical): string
    {
        $local = self::local($canonical);

        if ($local === null) {
            return self::COUNTRY_CODE.' ** ** ** **';
        }

        return sprintf(
            '%s %s ** ** %s',
            self::COUNTRY_CODE,
            substr($local, 0, 2),
            substr($local, -2),
        );
    }

    public static function local(?string $canonical): ?string
    {
        if ($canonical === null) {
            return null;
        }

        return preg_match('/^\+225(0[157][0-9]{8})$/', $canonical, $matches) === 1
            ? $matches[1]
            : null;
    }
}
