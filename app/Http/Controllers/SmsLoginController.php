<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SmsLoginService;
use App\Support\PhoneNumber;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Spatie\OneTimePasswords\Enums\ConsumeOneTimePasswordResult;

class SmsLoginController extends Controller
{
    public function __construct(private readonly SmsLoginService $sms)
    {
        abort_unless(config('vonage.api_key') || app()->environment(['local', 'testing']), 404);
    }

    public function showPhone(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        return view('auth.sms-phone');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $validated = $request->validate(['phone' => $this->phoneRules()], $this->phoneMessages());
        $phone = PhoneNumber::normalize($validated['phone']);

        if ($phone === null) {
            return redirect()->route('login.sms')->withErrors([
                'phone' => __('Numéro ivoirien invalide. Utilisez un numéro commençant par 07, 05 ou 01.'),
            ]);
        }

        $user = $this->sms->findLoginableCustomer($phone);

        if (! $user) {
            if ($this->sms->hitIpForUnknownNumber($request) === SmsLoginService::IP_LIMIT) {
                return $this->refused(__('Trop de demandes de code. Réessayez dans une heure.'), 'login.sms', 'phone');
            }

            return redirect()->route('login.sms.activate')
                ->withInput(['phone' => $validated['phone']])
                ->with('status', __('Aucun compte n\'est associé à ce numéro. Connectez-vous pour activer la connexion par SMS.'));
        }

        return $this->send($request, $user, $phone, false, 'login.sms', 'phone');
    }

    public function showActivation(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        return view('auth.sms-activate');
    }

    public function activate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'phone' => $this->phoneRules(),
        ], [
            'email.required' => __('L\'adresse email est obligatoire.'),
            'email.email' => __('L\'adresse email doit être valide.'),
            'password.required' => __('Le mot de passe est obligatoire.'),
        ] + $this->phoneMessages());

        $phone = PhoneNumber::normalize($validated['phone']);
        $user = $this->sms->findActivationCandidate($validated['email'], $validated['password']);

        if ($phone === null || ! $user) {
            return redirect()->route('login.sms.activate')
                ->withInput($request->only('email', 'phone'))
                ->withErrors(['email' => __('Ces informations ne correspondent à aucun compte client actif.')]);
        }

        if ($this->sms->phoneBelongsToAnotherUser($phone, $user->id)) {
            return redirect()->route('login.sms.activate')
                ->withInput($request->only('email', 'phone'))
                ->withErrors(['phone' => __('Ce numéro est déjà associé à un autre compte.')]);
        }

        return $this->send($request, $user, $phone, true, 'login.sms.activate', 'phone');
    }

    public function showCode(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        $pending = $this->sms->pending($request);

        if (! $pending) {
            return redirect()->route('login.sms')
                ->with('error', __('Votre session a expiré. Recommencez la connexion.'));
        }

        return view('auth.sms-code', [
            'phone' => PhoneNumber::mask($pending['phone']),
            'retryIn' => $this->sms->cooldownRemaining($pending['phone']),
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

        $pending = $this->sms->pending($request);

        if (! $pending) {
            return redirect()->route('login.sms')
                ->with('error', __('Votre session a expiré. Recommencez la connexion.'));
        }

        $user = $pending['user'];
        $phone = $pending['phone'];
        $activation = $pending['activation'];

        if (! $user->is_active || $user->role !== 'customer' || $user->isAdmin()) {
            $this->sms->clearPending($request);

            return redirect()->route('login.sms')
                ->with('error', __('Ces informations ne correspondent à aucun compte client actif.'));
        }

        $stillAllowed = $activation ? $user->canActivateSmsLogin() : $user->isSmsLoginable();

        if (! $stillAllowed) {
            $this->sms->clearPending($request);

            return redirect()->route('login.sms')
                ->with('error', __('Votre session a expiré. Recommencez la connexion.'));
        }

        if ($activation && $this->sms->phoneBelongsToAnotherUser($phone, $user->id)) {
            $this->sms->clearPending($request);

            return redirect()->route('login.sms')
                ->with('error', __('Ce numéro est déjà associé à un autre compte.'));
        }

        $result = $user->attemptLoginUsingOneTimePassword(
            $validated['code'],
            $request->boolean('remember'),
        );

        if ($result->isOk()) {
            if ($activation) {
                try {
                    $user->forceFill([
                        'phone' => $phone,
                        'phone_normalized' => $phone,
                        'phone_verified_at' => now(),
                    ])->save();
                } catch (QueryException) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login.sms')
                        ->with('error', __('Ce numéro est déjà associé à un autre compte.'));
                }
            }

            $request->session()->regenerate();
            $this->sms->clearPending($request);

            return redirect()->intended('/');
        }

        if ($result === ConsumeOneTimePasswordResult::RateLimitExceeded) {
            return $this->refused(__('Trop de tentatives. Réessayez dans une minute.'), 'login.sms.code', 'code');
        }

        $message = match ($result) {
            ConsumeOneTimePasswordResult::OneTimePasswordExpired => __('Ce code a expiré. Demandez un nouveau code.'),
            ConsumeOneTimePasswordResult::DifferentOrigin => __('Ce code a été demandé depuis un autre appareil ou réseau. Demandez un nouveau code.'),
            default => __('Code incorrect ou expiré. Demandez un nouveau code.'),
        };

        return redirect()->route('login.sms.code')->withErrors(['code' => $message]);
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $pending = $this->sms->pending($request);

        if (! $pending) {
            return redirect()->route('login.sms')
                ->with('error', __('Votre session a expiré. Recommencez la connexion.'));
        }

        $stillAllowed = $pending['activation']
            ? $pending['user']->canActivateSmsLogin()
            : $pending['user']->isSmsLoginable();

        if (! $stillAllowed) {
            $this->sms->clearPending($request);

            return redirect()->route('login.sms')
                ->with('error', __('Votre session a expiré. Recommencez la connexion.'));
        }

        return $this->send($request, $pending['user'], $pending['phone'], $pending['activation'], 'login.sms.code', 'code');
    }

    /** @param  string  $page  route to come back to when the code is refused; $field is the field that shows the message */
    private function send(Request $request, User $user, string $phone, bool $activation, string $page, string $field): RedirectResponse
    {
        $status = $this->sms->sendCode($user, $phone, $request, $activation);

        if ($status === SmsLoginService::SENT) {
            // The code page already names the masked number; a flash here would repeat it.
            return redirect()->route('login.sms.code');
        }

        $message = match ($status) {
            SmsLoginService::COOLDOWN => __('Veuillez patienter avant de demander un nouveau code.'),
            SmsLoginService::PHONE_LIMIT, SmsLoginService::IP_LIMIT => __('Trop de demandes de code. Réessayez dans une heure.'),
            default => __('L\'envoi du SMS a échoué. Réessayez dans un instant.'),
        };

        return $this->refused($message, $page, $field)->withInput();
    }

    /** @return array<int, mixed> */
    private function phoneRules(): array
    {
        return [
            'required',
            'string',
            'max:20',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (PhoneNumber::normalize(is_string($value) ? $value : null) === null) {
                    $fail(__('Numéro ivoirien invalide. Utilisez un numéro commençant par 07, 05 ou 01.'));
                }
            },
        ];
    }

    /** @return array<string, string> */
    private function phoneMessages(): array
    {
        return [
            'phone.required' => __('Le numéro de téléphone est obligatoire.'),
            'phone.max' => __('Le numéro de téléphone est trop long.'),
        ];
    }

    /** A refusal is shown on a page of the flow, in French, with a way back; never as a bare error screen. */
    private function refused(string $message, string $page, string $field): RedirectResponse
    {
        return redirect()->route($page)->withErrors([$field => $message]);
    }
}
