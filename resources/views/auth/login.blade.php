@php
    // Only offer a provider when the shop actually has credentials for it
    $social = collect(['google' => 'Google', 'facebook' => 'Facebook'])->filter(fn ($label, $key) => config("services.{$key}.client_id"));
@endphp

<x-layouts.storefront title="Connexion" description="Connectez-vous à votre compte Glowing Cosmetics." robots="noindex,follow">
    <x-storefront.auth-card title="Connexion" :crumbs="[['label' => 'Connexion']]">
        <form method="POST" action="{{ route('login.post') }}" class="grid gap-5">
            @csrf
            <x-ui.field label="Adresse e-mail" name="email" type="email" :value="old('email')" :error="$errors->first('email')" autocomplete="email" inputmode="email" required autofocus />
            <x-ui.field label="Mot de passe" name="password" type="password" :error="$errors->first('password')" autocomplete="current-password" required />

            <div class="flex flex-wrap items-center justify-between gap-x-4">
                <label class="flex min-h-11 cursor-pointer items-center gap-3">
                    <input type="checkbox" name="remember" value="1" class="size-5 accent-green" @checked(old('remember'))>
                    <span>Rester connecté</span>
                </label>
                <a href="{{ route('password.request') }}" class="inline-flex min-h-11 items-center underline">Mot de passe oublié ?</a>
            </div>

            <x-ui.button type="submit" class="w-full">Se connecter</x-ui.button>
        </form>

        @if ($social->isNotEmpty())
            <div class="grid gap-2 border-t border-line pt-5">
                <p class="text-sm text-ink-muted">Ou continuez avec</p>
                @foreach ($social as $key => $label)
                    <x-ui.button variant="secondary" :href="route('social.redirect', $key)" class="w-full">{{ $label }}</x-ui.button>
                @endforeach
            </div>
        @endif

        <div class="border-t border-line pt-5">
            <h2 class="text-2xl">Nouveau client ?</h2>
            <p class="mt-2 text-ink-muted">Un compte vous permet de retrouver vos commandes et votre panier sur tous vos appareils. Commander reste possible sans compte.</p>
            <x-ui.button variant="secondary" :href="route('register')" class="mt-4 w-full">Créer un compte</x-ui.button>
        </div>
    </x-storefront.auth-card>
</x-layouts.storefront>
