<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the language of the page: the visitor's choice (cookie set by the switcher), else the best match of the
 * browser's Accept-Language among the languages the shop offers, else French.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $available = array_keys(config('locales.available'));
        $chosen = $request->cookie(config('locales.cookie'));

        if (is_string($chosen) && in_array($chosen, $available, true)) {
            return $chosen;
        }

        // Accept-Language, e.g. "en-GB,en;q=0.9,fr;q=0.8": first language we offer, in the visitor's order
        $preferred = $request->getLanguages();   // already sorted by quality, like "en_GB", "en", "fr"
        foreach ($preferred as $language) {
            $code = strtolower(substr(str_replace('_', '-', $language), 0, 2));
            if (in_array($code, $available, true)) {
                return $code;
            }
        }

        return config('locales.default');
    }
}
