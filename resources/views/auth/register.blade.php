@php
    $social = collect(['google' => 'Google', 'facebook' => 'Facebook'])->filter(fn ($label, $key) => config("services.{$key}.client_id"));
@endphp

<x-layouts.storefront title="Créer un compte" description="Créez votre compte Glowing Cosmetics pour suivre vos commandes." robots="noindex,follow">
    <x-storefront.auth-card title="Créer un compte" :crumbs="[['label' => 'Connexion', 'href' => route('login')], ['label' => 'Inscription']]">
        <form method="POST" action="{{ route('register.post') }}" class="grid gap-5">
            @csrf
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.field label="Prénom" name="first_name" :value="old('first_name')" :error="$errors->first('first_name')" autocomplete="given-name" required autofocus />
                <x-ui.field label="Nom" name="last_name" :value="old('last_name')" :error="$errors->first('last_name')" autocomplete="family-name" required />
            </div>
            <x-ui.field label="Adresse e-mail" name="email" type="email" :value="old('email')" :error="$errors->first('email')" autocomplete="email" inputmode="email" required />
            <x-ui.field label="Mot de passe" name="password" type="password" :error="$errors->first('password')" autocomplete="new-password" required minlength="8" help="8 caractères minimum." />
            <x-ui.field label="Confirmez le mot de passe" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8" />

            <div>
                <label class="flex min-h-11 cursor-pointer items-start gap-3 py-2">
                    <input type="checkbox" id="field-terms" name="terms" value="1" required class="mt-0.5 size-5 shrink-0 accent-green" @checked(old('terms')) @if ($errors->has('terms')) aria-invalid="true" @endif>
                    <span>J'accepte la <a href="{{ route('privacy') }}" class="underline">politique de confidentialité</a> et les <a href="{{ route('terms') }}" class="underline">conditions d'utilisation</a>.</span>
                </label>
                @error('terms')<p class="flex items-start gap-1.5 text-sm font-medium text-danger"><x-ui.icon name="alert" class="mt-0.5 size-4" /> {{ $message }}</p>@enderror
            </div>

            <x-ui.button type="submit" class="w-full">Créer mon compte</x-ui.button>
        </form>

        @if ($social->isNotEmpty())
            <div class="grid gap-2 border-t border-line pt-5">
                <p class="text-sm text-ink-muted">Ou inscrivez-vous avec</p>
                @foreach ($social as $key => $label)
                    <x-ui.button variant="secondary" :href="route('social.redirect', $key)" class="w-full">{{ $label }}</x-ui.button>
                @endforeach
            </div>
        @endif

        <p class="border-t border-line pt-5">Déjà un compte ? <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center font-semibold underline">Se connecter</a></p>
    </x-storefront.auth-card>
</x-layouts.storefront>
