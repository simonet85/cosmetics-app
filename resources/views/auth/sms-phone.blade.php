<x-layouts.storefront :title="__('Connexion par SMS')" :description="__('Connectez-vous à votre compte Glowing Cosmetics avec un code reçu par SMS.')" robots="noindex,follow">
    <x-storefront.auth-card :title="__('Connexion par SMS')" :crumbs="[['label' => 'Connexion', 'href' => route('login')], ['label' => 'Connexion par SMS']]">
        <p class="text-ink-muted">{{ __('Nous vous envoyons un code à 6 chiffres par SMS. Aucun mot de passe à retenir.') }}</p>

        <form method="POST" action="{{ route('login.sms.send') }}" class="grid gap-5">
            @csrf
            <x-ui.field :label="__('Numéro de téléphone')" name="phone" type="tel" :value="old('phone')" :error="$errors->first('phone')" :help="__('Exemple : 07 12 34 56 78')" autocomplete="tel" inputmode="tel" required autofocus />
            <x-ui.button type="submit" class="w-full">{{ __('Recevoir mon code') }}</x-ui.button>
        </form>

        <p class="border-t border-line pt-5">{{ __('Vous préférez votre mot de passe ?') }} <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center font-semibold underline">{{ __('Se connecter') }}</a></p>
    </x-storefront.auth-card>
</x-layouts.storefront>
