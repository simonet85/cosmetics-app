<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * The sentences resources/js/storefront.js shows, in the current language.
 *
 * The script calls __('French sentence', { vars }) like PHP does; this class finds those literals in the source
 * and the layout prints their translations as JSON, so a new sentence in the script needs no PHP change.
 */
class JsStrings
{
    private const SOURCE = 'resources/js/storefront.js';

    /** @return array<string, string> French sentence => sentence in the current language */
    public static function all(): array
    {
        $out = [];
        foreach (self::keys() as $key) {
            $out[$key] = __($key);
        }

        return $out;
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        $path = base_path(self::SOURCE);

        if (! is_file($path)) {
            return [];
        }

        return Cache::remember('js-string-keys:'.filemtime($path), now()->addDay(), function () use ($path) {
            preg_match_all("/__\('((?:\\\\.|[^'\\\\])*)'/", (string) file_get_contents($path), $matches);

            return array_values(array_unique(array_map(fn ($literal) => stripslashes($literal), $matches[1])));
        });
    }
}
