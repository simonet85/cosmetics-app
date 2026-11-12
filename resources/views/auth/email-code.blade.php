<x-layouts.storefront :title="__('Code de connexion')" :description="__('Saisissez le code reçu par e-mail pour vous connecter.')" robots="noindex,nofollow">
    <x-storefront.auth-card :title="__('Code de connexion')" :crumbs="[['label' => 'Connexion', 'href' => route('login')], ['label' => 'Connexion par e-mail', 'href' => route('login.email')], ['label' => 'Code']]">
        <p class="text-ink-muted">{{ __('Si un compte existe pour :email, un code à 6 chiffres vient d\'y être envoyé. Pensez à regarder vos courriers indésirables.', ['email' => $email]) }}</p>

        <form method="POST" action="{{ route('login.email.code.verify') }}" class="grid gap-5">
            @csrf
            <x-ui.field :label="__('Code reçu par e-mail')" name="code" :error="$errors->first('code')" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus />

            <label class="flex min-h-11 cursor-pointer items-center gap-3">
                <input type="checkbox" name="remember" value="1" class="size-5 accent-green" @checked(old('remember'))>
                <span>{{ __('Rester connecté') }}</span>
            </label>

            <x-ui.button type="submit" class="w-full">{{ __('Vérifier et me connecter') }}</x-ui.button>
        </form>

        <div class="flex flex-wrap items-center justify-between gap-x-4 border-t border-line pt-5">
            <form method="POST" action="{{ route('login.email.resend') }}">
                @csrf
                <x-ui.button variant="text" type="submit" id="resend-button">{{ __('Renvoyer le code') }}</x-ui.button>
            </form>
            <a href="{{ route('login.email') }}" class="inline-flex min-h-11 items-center underline">{{ __('Changer d\'adresse') }}</a>
        </div>

        <p>{{ __('Vous préférez votre mot de passe ?') }} <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center font-semibold underline">{{ __('Se connecter') }}</a></p>
    </x-storefront.auth-card>

    @include('auth.partials.resend-countdown')
</x-layouts.storefront>
