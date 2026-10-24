<x-layouts.storefront :title="__('Nouveau mot de passe')" robots="noindex,nofollow">
    <x-storefront.auth-card :title="__('Nouveau mot de passe')" :crumbs="[['label' => 'Nouveau mot de passe']]">
        <form method="POST" action="{{ route('password.update') }}" class="grid gap-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-ui.field :label="__('Adresse e-mail')" name="email" type="email" :value="old('email', $email)" :error="$errors->first('email')" autocomplete="email" inputmode="email" required />
            <x-ui.field :label="__('Nouveau mot de passe')" name="password" type="password" :error="$errors->first('password')" autocomplete="new-password" required minlength="8" help="8 caractères minimum." />
            <x-ui.field :label="__('Confirmez le mot de passe')" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8" />
            <x-ui.button type="submit" class="w-full">{{ __('Enregistrer le mot de passe') }}</x-ui.button>
        </form>
    </x-storefront.auth-card>
</x-layouts.storefront>
