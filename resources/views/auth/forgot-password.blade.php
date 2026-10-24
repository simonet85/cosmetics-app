<x-layouts.storefront :title="__('Mot de passe oublié')" robots="noindex,follow">
    <x-storefront.auth-card :title="__('Mot de passe oublié')" :crumbs="[['label' => 'Connexion', 'href' => route('login')], ['label' => 'Mot de passe oublié']]">
        <p class="text-ink-muted">{{ __('Indiquez l\'adresse e-mail de votre compte. Nous vous envoyons un lien pour choisir un nouveau mot de passe.') }}</p>

        <form method="POST" action="{{ route('password.email') }}" class="grid gap-5">
            @csrf
            <x-ui.field :label="__('Adresse e-mail')" name="email" type="email" :value="old('email')" :error="$errors->first('email')" autocomplete="email" inputmode="email" required autofocus />
            <x-ui.button type="submit" class="w-full">{{ __('Envoyer le lien') }}</x-ui.button>
        </form>

        <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center underline">{{ __('Retour à la connexion') }}</a>
    </x-storefront.auth-card>
</x-layouts.storefront>
