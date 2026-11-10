<x-layouts.storefront :title="__('Activer la connexion par SMS')" :description="__('Activez la connexion par SMS sur votre compte Glowing Cosmetics.')" robots="noindex,follow">
    <x-storefront.auth-card :title="__('Activer la connexion par SMS')" :crumbs="[['label' => 'Connexion', 'href' => route('login')], ['label' => 'Connexion par SMS', 'href' => route('login.sms')], ['label' => 'Activation']]">
        <p class="text-ink-muted">{{ __('Confirmez votre compte avec votre mot de passe, puis validez votre numéro avec le code reçu par SMS. Ensuite, votre téléphone suffira pour vous connecter.') }}</p>

        <form method="POST" action="{{ route('login.sms.activate.send') }}" class="grid gap-5">
            @csrf
            <x-ui.field :label="__('Adresse e-mail')" name="email" type="email" :value="old('email')" :error="$errors->first('email')" autocomplete="email" inputmode="email" required autofocus />
            <x-ui.field :label="__('Mot de passe')" name="password" type="password" :error="$errors->first('password')" :help="__('Mot de passe saisi une seule fois, pour activer la connexion par SMS.')" autocomplete="current-password" required />
            <x-ui.field :label="__('Numéro de téléphone')" name="phone" type="tel" :value="old('phone')" :error="$errors->first('phone')" :help="__('Exemple : 07 12 34 56 78')" autocomplete="tel" inputmode="tel" required />
            <x-ui.button type="submit" class="w-full">{{ __('Recevoir mon code') }}</x-ui.button>
        </form>

        <p class="border-t border-line pt-5">{{ __('Mauvais numéro ?') }} <a href="{{ route('login.sms') }}" class="inline-flex min-h-11 items-center font-semibold underline">{{ __('Changer de numéro') }}</a></p>
    </x-storefront.auth-card>
</x-layouts.storefront>
