<x-layouts.storefront :title="__('Connexion par e-mail')" :description="__('Connectez-vous à votre compte Glowing Cosmetics avec un code reçu par e-mail.')" robots="noindex,follow">
    <x-storefront.auth-card :title="__('Connexion par e-mail')" :crumbs="[['label' => 'Connexion', 'href' => route('login')], ['label' => 'Connexion par e-mail']]">
        <p class="text-ink-muted">{{ __('Nous vous envoyons un code à 6 chiffres par e-mail. Aucun mot de passe à retenir.') }}</p>

        <form method="POST" action="{{ route('login.email.send') }}" class="grid gap-5">
            @csrf
            <x-ui.field :label="__('Adresse e-mail')" name="email" type="email" :value="old('email')" :error="$errors->first('email')" autocomplete="email" inputmode="email" required autofocus />
            <x-ui.button type="submit" class="w-full">{{ __('Recevoir mon code') }}</x-ui.button>
        </form>

        <p class="border-t border-line pt-5">{{ __('Vous préférez votre mot de passe ?') }} <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center font-semibold underline">{{ __('Se connecter') }}</a></p>
    </x-storefront.auth-card>
</x-layouts.storefront>
