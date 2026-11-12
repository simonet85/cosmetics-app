<?php

namespace App\Http\Controllers;

use App\Services\EmailLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Spatie\OneTimePasswords\Enums\ConsumeOneTimePasswordResult;

class EmailLoginController extends Controller
{
    public function __construct(private readonly EmailLoginService $emailLogin) {}

    public function showEmail(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        return view('auth.email-address');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ], [
            'email.required' => __('L\'adresse email est obligatoire.'),
            'email.email' => __('L\'adresse email doit être valide.'),
        ]);

        $email = EmailLoginService::normalize($validated['email']);
        $status = $this->emailLogin->sendCode($this->emailLogin->findLoginableCustomer($email), $email, $request);

        return $this->afterSend($request, $status, $email, 'email');
    }

    public function showCode(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        $pending = $this->emailLogin->pending($request);

        if (! $pending) {
            return $this->expired();
        }

        return view('auth.email-code', [
            'email' => EmailLoginService::mask($pending['email']),
            'retryIn' => $this->emailLogin->cooldownRemaining($pending['email']),
        ]);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
            'remember' => ['sometimes', 'boolean'],
        ], [
            'code.required' => __('Le code est obligatoire.'),
            'code.digits' => __('Le code doit contenir 6 chiffres.'),
        ]);

        $pending = $this->emailLogin->pending($request);

        if (! $pending) {
            return $this->expired();
        }

        $user = $pending['user'];

        // An unknown address has no code to guess: it ends on the same message as a wrong code.
        if (! $user || ! $this->emailLogin->findLoginableCustomer($pending['email'])) {
            return redirect()->route('login.email.code')->withErrors(['code' => $this->wrongCode()]);
        }

        $result = $user->attemptLoginUsingOneTimePassword($validated['code'], $request->boolean('remember'));

        if ($result->isOk()) {
            $request->session()->regenerate();
            $this->emailLogin->clearPending($request);

            return redirect()->intended('/');
        }

        $message = match ($result) {
            ConsumeOneTimePasswordResult::RateLimitExceeded => __('Trop de tentatives. Réessayez dans une minute.'),
            ConsumeOneTimePasswordResult::OneTimePasswordExpired => __('Ce code a expiré. Demandez un nouveau code.'),
            ConsumeOneTimePasswordResult::DifferentOrigin => __('Ce code a été demandé depuis un autre appareil ou réseau. Demandez un nouveau code.'),
            default => $this->wrongCode(),
        };

        return redirect()->route('login.email.code')->withErrors(['code' => $message]);
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $pending = $this->emailLogin->pending($request);

        if (! $pending) {
            return $this->expired();
        }

        $status = $this->emailLogin->sendCode(
            $this->emailLogin->findLoginableCustomer($pending['email']),
            $pending['email'],
            $request,
        );

        return $this->afterSend($request, $status, $pending['email'], 'code');
    }

    /** Every outcome lands on a page of the flow with a message in French; none ends on a bare error screen. */
    private function afterSend(Request $request, string $status, string $email, string $from): RedirectResponse
    {
        if ($status === EmailLoginService::SENT) {
            return redirect()->route('login.email.code')
                ->with('status', $from === 'code' ? __('Un nouveau code vient d\'être envoyé.') : null);
        }

        $message = match ($status) {
            EmailLoginService::COOLDOWN => __('Veuillez patienter une minute avant de demander un nouveau code.'),
            EmailLoginService::FAILED => __('L\'envoi de l\'e-mail a échoué. Réessayez dans un instant.'),
            default => __('Trop de demandes de code. Réessayez dans une heure.'),
        };

        // A code was already sent to this address a moment ago: send the customer back to that screen.
        if ($from === 'code' || ($status === EmailLoginService::COOLDOWN && $this->emailLogin->pending($request))) {
            return redirect()->route('login.email.code')->withErrors(['code' => $message]);
        }

        return redirect()->route('login.email')->withInput(['email' => $email])->withErrors(['email' => $message]);
    }

    private function expired(): RedirectResponse
    {
        return redirect()->route('login.email')->with('error', __('Votre session a expiré. Recommencez la connexion.'));
    }

    private function wrongCode(): string
    {
        return __('Code incorrect ou expiré. Demandez un nouveau code.');
    }
}
