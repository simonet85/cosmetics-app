@php
    $email = trim((string) getSetting('contact_email', ''));
    $phone = trim((string) getSetting('phone', ''));
@endphp

<x-layouts.storefront :title="__('Contact')" :description="__('Écrivez à l\'équipe Glowing Cosmetics : une question sur une commande, un produit ou un retour.')">
    <x-storefront.info-page :title="__('Contactez-nous')" width="max-w-5xl">
        @if (session('success'))
            <p role="status" class="mb-6 border border-green bg-mint px-4 py-3 font-semibold">{{ session('success') }}</p>
        @endif

        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <form method="POST" action="{{ route('pages.contact.submit') }}" class="grid gap-5">
                @csrf
                <p class="text-ink-muted">{{ __('Une question sur une commande ? Indiquez son numéro dans votre message.') }}</p>
                <x-ui.field :label="__('Nom complet')" name="name" :value="old('name', auth()->user()?->full_name)" :error="$errors->first('name')" autocomplete="name" required />
                <x-ui.field :label="__('Adresse e-mail')" name="email" type="email" :value="old('email', auth()->user()?->email)" :error="$errors->first('email')" autocomplete="email" inputmode="email" required />
                <x-ui.field :label="__('Téléphone (facultatif)')" name="phone" type="tel" :value="old('phone')" :error="$errors->first('phone')" autocomplete="tel" inputmode="tel" />
                <x-ui.field :label="__('Sujet')" name="subject" :value="old('subject')" :error="$errors->first('subject')" required />
                <div class="grid gap-1.5">
                    <label for="field-message" class="text-sm font-semibold">{{ __('Message') }} <span class="text-danger" aria-hidden="true">*</span><span class="sr-only"> {{ __('(obligatoire)') }}</span></label>
                    <textarea id="field-message" name="message" rows="6" required maxlength="5000" @if ($errors->has('message')) aria-invalid="true" aria-describedby="field-message-error" @endif
                              class="w-full rounded-sm border bg-white p-3 text-base {{ $errors->has('message') ? 'border-danger' : 'border-field' }}">{{ old('message') }}</textarea>
                    @error('message')<p id="field-message-error" class="flex items-start gap-1.5 text-sm font-medium text-danger"><x-ui.icon name="alert" class="mt-0.5 size-4" /> {{ $message }}</p>@enderror
                </div>
                <div><x-ui.button type="submit" class="w-full sm:w-auto">{{ __('Envoyer le message') }}</x-ui.button></div>
            </form>

            <aside aria-labelledby="direct-title" class="border border-ink bg-white p-5 self-start">
                <h2 id="direct-title" class="!mt-0 text-2xl">{{ __('Nous joindre') }}</h2>
                @if ($email === '' && $phone === '')
                    <p class="mt-3 text-ink-muted">{{ __('Utilisez le formulaire : nous vous répondons par e-mail.') }}</p>
                @else
                    <dl class="mt-3 grid gap-3">
                        @if ($email !== '')
                            <div><dt class="label-caps text-ink-muted">{{ __('E-mail') }}</dt><dd class="break-all"><a href="mailto:{{ $email }}" class="underline">{{ $email }}</a></dd></div>
                        @endif
                        @if ($phone !== '')
                            <div><dt class="label-caps text-ink-muted">{{ __('Téléphone') }}</dt><dd><a href="tel:{{ preg_replace('/[^+\d]/', '', $phone) }}" class="underline">{{ $phone }}</a></dd></div>
                        @endif
                    </dl>
                @endif
                <p class="mt-4 text-sm"><a href="{{ route('pages.faq') }}" class="inline-flex min-h-11 items-center underline">{{ __('Voir les questions fréquentes') }}</a></p>
            </aside>
        </div>
    </x-storefront.info-page>
</x-layouts.storefront>
