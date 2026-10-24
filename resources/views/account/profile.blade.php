<x-layouts.storefront :title="__('Mon profil')" robots="noindex,nofollow">
    <x-storefront.account-shell :title="__('Mon profil')" current="profile">
        <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data" class="grid max-w-2xl gap-10">
            @csrf
            @method('PUT')

            <section aria-labelledby="identity-title" class="grid gap-4">
                <h2 id="identity-title" class="text-3xl">{{ __('Mes informations') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field :label="__('Prénom')" name="first_name" :value="old('first_name', $user->first_name)" :error="$errors->first('first_name')" autocomplete="given-name" required />
                    <x-ui.field :label="__('Nom')" name="last_name" :value="old('last_name', $user->last_name)" :error="$errors->first('last_name')" autocomplete="family-name" required />
                    <x-ui.field :label="__('Adresse e-mail')" name="email" type="email" :value="old('email', $user->email)" :error="$errors->first('email')" autocomplete="email" inputmode="email" required wrapper="sm:col-span-2" />
                    <x-ui.field :label="__('Téléphone')" name="phone" type="tel" :value="old('phone', $user->phone)" :error="$errors->first('phone')" autocomplete="tel" inputmode="tel" wrapper="sm:col-span-2" />
                </div>
            </section>

            <section aria-labelledby="address-title" class="grid gap-4">
                <h2 id="address-title" class="text-3xl">{{ __('Adresse') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.field :label="__('Adresse')" name="address" :value="old('address', $user->address)" :error="$errors->first('address')" autocomplete="street-address" wrapper="sm:col-span-2" />
                    <x-ui.field :label="__('Ville')" name="city" :value="old('city', $user->city)" :error="$errors->first('city')" autocomplete="address-level2" />
                    <x-ui.field :label="__('Code postal')" name="postal_code" :value="old('postal_code', $user->postal_code)" :error="$errors->first('postal_code')" autocomplete="postal-code" />
                    <x-ui.field :label="__('Pays')" name="country" :value="old('country', $user->country)" :error="$errors->first('country')" autocomplete="country-name" wrapper="sm:col-span-2" />
                </div>
            </section>

            <section aria-labelledby="photo-title" class="grid gap-3">
                <h2 id="photo-title" class="text-3xl">{{ __('Photo de profil') }}</h2>
                <div class="flex items-center gap-4">
                    @if ($user->avatar)
                        <img src="{{ asset($user->avatar) }}" alt="{{ __('Votre photo actuelle') }}" width="80" height="80" class="size-20 border border-ink object-cover">
                    @endif
                    <div class="grid gap-1.5">
                        <label for="field-avatar" class="text-sm font-semibold">{{ __('Choisir une image') }}</label>
                        <input id="field-avatar" type="file" name="avatar" accept="image/jpeg,image/png" aria-describedby="avatar-help" class="text-sm file:mr-3 file:min-h-11 file:cursor-pointer file:border file:border-ink file:bg-white file:px-4 file:font-semibold">
                        <p id="avatar-help" class="text-sm text-ink-muted">{{ __('JPG ou PNG, 2 Mo maximum.') }}</p>
                        @error('avatar')<p class="text-sm font-medium text-danger">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section aria-labelledby="password-title" class="grid gap-4">
                <h2 id="password-title" class="text-3xl">{{ __('Mot de passe') }}</h2>
                <p class="text-ink-muted">{{ __('Laissez ces champs vides pour garder votre mot de passe actuel.') }}</p>
                <x-ui.field :label="__('Mot de passe actuel')" name="current_password" type="password" :error="$errors->first('current_password')" autocomplete="current-password" />
                <x-ui.field :label="__('Nouveau mot de passe')" name="new_password" type="password" :error="$errors->first('new_password')" autocomplete="new-password" minlength="8" help="8 caractères minimum." />
                <x-ui.field :label="__('Confirmez le nouveau mot de passe')" name="new_password_confirmation" type="password" autocomplete="new-password" minlength="8" />
            </section>

            <div><x-ui.button type="submit" class="w-full sm:w-auto">{{ __('Enregistrer') }}</x-ui.button></div>
        </form>
    </x-storefront.account-shell>
</x-layouts.storefront>
