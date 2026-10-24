<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('locales.available')), 404);

        // Back to the page the visitor was on, never to another site
        $previous = url()->previous();
        $target = str_starts_with($previous, url('/')) ? $previous : route('home');

        return redirect($target)->withCookie(Cookie::make(config('locales.cookie'), $locale, 60 * 24 * 365, '/', null, request()->isSecure(), true, false, 'lax'));
    }
}
