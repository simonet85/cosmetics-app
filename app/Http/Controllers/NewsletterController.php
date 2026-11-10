<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('newsletter', [
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => __('Indiquez votre adresse e-mail.'),
            'email.email' => __('L\'adresse e-mail doit être valide.'),
        ]);

        $email = mb_strtolower(trim($validated['email']));

        $subscriber = NewsletterSubscriber::firstOrCreate(
            ['email' => $email],
            ['token' => Str::random(32), 'subscribed_at' => now()],
        );

        // Someone who unsubscribed earlier can sign up again with the same address
        if (! $subscriber->wasRecentlyCreated && $subscriber->unsubscribed_at !== null) {
            $subscriber->update(['subscribed_at' => now(), 'unsubscribed_at' => null]);
        }

        return back()->with('newsletter_success', __('Merci ! Votre adresse e-mail est bien inscrite à la newsletter.'));
    }

    /** One-click unsubscribe from an emailed link: the random token is the proof of ownership. */
    public function unsubscribe(string $token): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->firstOrFail();

        if ($subscriber->unsubscribed_at === null) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        return redirect()->route('home')
            ->with('newsletter_success', __('Votre adresse e-mail a bien été désinscrite de la newsletter.'));
    }
}
