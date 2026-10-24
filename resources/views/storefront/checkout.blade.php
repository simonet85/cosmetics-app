@php
    $user = auth()->user();
    $taxRate = rtrim(rtrim(number_format((float) getSetting('tax_rate', '15'), 2, ',', ''), '0'), ',');
    $itemCount = collect($cartItems)->sum('quantity');
    $methods = collect(\App\Support\PaymentMethods::labels())->filter(fn ($label, $key) => $enabledPaymentMethods[$key] ?? false);
    $descriptions = [
        'moneyfusion' => __('Orange Money, MTN Money, Wave et autres, via MoneyFusion. Vous êtes redirigé pour confirmer le paiement.'),
        'cash_on_delivery' => __('Vous payez en espèces à la réception de votre commande.'),
        'bank_transfer' => __('Vous payez par virement bancaire. La commande est traitée à réception du virement.'),
        'credit_card' => __('Paiement par carte bancaire.'),
        'paypal' => __('Paiement avec votre compte PayPal.'),
    ];
    $chosen = old('payment_method', $methods->count() === 1 ? $methods->keys()->first() : null);
    $billingDifferent = old('_token') && ! old('billing_same_as_shipping');

    // Error summary: each message links to its field
    $errorList = collect($errors->getMessages())->map(fn ($messages, $field) => [$field, $messages[0]])->values();
@endphp

<x-layouts.storefront :title="__('Paiement')" :description="__('Finalisez votre commande Glowing Cosmetics.')" robots="noindex,nofollow">
    <div class="wrap py-6 sm:py-10">
        <nav aria-label="{{ __('Fil d\'Ariane') }}" class="flex flex-wrap items-center text-sm text-ink-muted">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center underline">{{ __('Accueil') }}</a>
            <span aria-hidden="true" class="mx-2">/</span>
            <a href="{{ route('cart.index') }}" class="inline-flex min-h-11 items-center underline">{{ __('Panier') }}</a>
            <span aria-hidden="true" class="mx-2">/</span>
            <span aria-current="page" class="text-ink">{{ __('Paiement') }}</span>
        </nav>

        <h1 class="mt-2 text-4xl uppercase sm:text-5xl">{{ __('Paiement') }}</h1>

        @if (session('error'))
            <div role="alert" class="mt-6 flex items-start gap-3 border border-danger bg-white p-4 font-semibold text-danger">
                <x-ui.icon name="alert" class="mt-0.5 size-5 shrink-0" />
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @if ($errorList->isNotEmpty())
            <div role="alert" tabindex="-1" data-error-summary class="mt-6 border border-danger bg-white p-4">
                <p class="flex items-center gap-2 font-semibold text-danger"><x-ui.icon name="alert" class="size-5" /> {{ __('Corrigez les points suivants pour continuer :') }}</p>
                <ul class="mt-2 list-disc pl-6">
                    @foreach ($errorList as [$field, $message])
                        <li><a href="#field-{{ $field }}" class="underline">{{ $message }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('checkout.process') }}" method="POST" data-checkout class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-start lg:gap-12">
            @csrf
            <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">

            <div class="grid gap-10">
                {{-- Delivery --}}
                <section aria-labelledby="delivery-title">
                    <h2 id="delivery-title" class="text-3xl">{{ __('Livraison') }}</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <x-ui.field :label="__('Prénom')" name="first_name" :value="old('first_name', $user?->first_name)" :error="$errors->first('first_name')" autocomplete="given-name" required />
                        <x-ui.field :label="__('Nom')" name="last_name" :value="old('last_name', $user?->last_name)" :error="$errors->first('last_name')" autocomplete="family-name" required />
                        <x-ui.field :label="__('E-mail')" name="email" type="email" :value="old('email', $user?->email)" :error="$errors->first('email')" autocomplete="email" inputmode="email" required
                                    :help="__('Votre confirmation de commande y est envoyée.')" wrapper="sm:col-span-2" />
                        <x-ui.field :label="__('Téléphone')" name="phone" type="tel" :value="old('phone', $user?->phone)" :error="$errors->first('phone')" autocomplete="tel" inputmode="tel" required
                                    :help="__('Pour le livreur et pour le paiement mobile.')" />
                        <x-ui.field :label="__('Ville')" name="city" :value="old('city', $user?->city)" :error="$errors->first('city')" autocomplete="address-level2" required />
                        <x-ui.field :label="__('Quartier')" name="quartier" :value="old('quartier')" :error="$errors->first('quartier')" autocomplete="address-line1" required
                                    :help="__('Quartier et repère pour trouver l\'adresse.')" wrapper="sm:col-span-2" />
                    </div>

                    {{-- Checkbox, label and fields are siblings so plain CSS shows the fields when it is unchecked (no script needed) --}}
                    <div class="mt-4 flex flex-wrap items-center gap-x-3">
                        <input type="checkbox" id="billing-same" name="billing_same_as_shipping" value="1" class="peer size-5 accent-green"
                               @checked(old('billing_same_as_shipping', ! $billingDifferent))>
                        <label for="billing-same" class="flex min-h-11 cursor-pointer items-center">{{ __('Adresse de facturation identique à la livraison') }}</label>

                        <div class="mt-2 hidden basis-full gap-4 sm:grid-cols-2 peer-[:not(:checked)]:grid" id="billing-fields">
                            <x-ui.field :label="__('Prénom de facturation')" name="billing_first_name" :value="old('billing_first_name')" :error="$errors->first('billing_first_name')" autocomplete="billing given-name" />
                            <x-ui.field :label="__('Nom de facturation')" name="billing_last_name" :value="old('billing_last_name')" :error="$errors->first('billing_last_name')" autocomplete="billing family-name" />
                            <x-ui.field :label="__('Ville de facturation')" name="billing_city" :value="old('billing_city')" :error="$errors->first('billing_city')" autocomplete="billing address-level2" />
                            <x-ui.field :label="__('Quartier de facturation')" name="billing_quartier" :value="old('billing_quartier')" :error="$errors->first('billing_quartier')" autocomplete="billing address-line1" />
                        </div>
                    </div>
                </section>

                {{-- Payment --}}
                <fieldset aria-describedby="payment-help">
                    <legend class="text-3xl">{{ __('Paiement') }}</legend>
                    <p id="payment-help" class="mt-1 text-ink-muted">{{ __('Choisissez comment vous réglez cette commande.') }}</p>

                    @if ($methods->isEmpty())
                        <p class="mt-4 border border-danger p-4 font-semibold text-danger">{{ __('Aucun mode de paiement n\'est disponible pour le moment. Contactez-nous pour passer commande.') }}</p>
                    @else
                        <div class="mt-4 grid gap-2">
                            @foreach ($methods as $key => $label)
                                <label class="relative block">
                                    <input type="radio" name="payment_method" value="{{ $key }}" class="peer sr-only" required @checked($chosen === $key)>
                                    <span class="flex min-h-14 cursor-pointer items-start gap-3 border border-field p-4 peer-checked:[&_.dot]:block peer-checked:border-2 peer-checked:border-ink peer-checked:bg-mint peer-focus-visible:outline peer-focus-visible:outline-[3px] peer-focus-visible:outline-offset-2 peer-focus-visible:outline-green-deep">
                                        <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border border-ink" aria-hidden="true">
                                            <span class="dot hidden size-2.5 rounded-full bg-ink"></span>
                                        </span>
                                        <span>
                                            <span class="block font-semibold">{{ $label }}</span>
                                            <span class="block text-sm text-ink-muted">{{ $descriptions[$key] ?? '' }}</span>
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('payment_method')
                            <p id="field-payment_method" class="mt-2 flex items-start gap-1.5 text-sm font-medium text-danger"><x-ui.icon name="alert" class="mt-0.5 size-4" /> {{ $message }}</p>
                        @enderror
                    @endif
                </fieldset>

                {{-- Notes --}}
                <div class="grid gap-1.5">
                    <label for="field-notes" class="text-sm font-semibold">{{ __('Instructions pour la livraison') }} <span class="font-normal text-ink-muted">{{ __('(facultatif)') }}</span></label>
                    <textarea id="field-notes" name="notes" rows="3" maxlength="1000" class="w-full rounded-sm border border-field bg-white p-3 text-base">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- Summary --}}
            <aside class="border border-ink bg-white p-5 lg:sticky lg:top-24" aria-labelledby="summary-title">
                <h2 id="summary-title" class="text-2xl">{{ __('Votre commande') }}</h2>
                <ul class="mt-4 divide-y divide-line border-y border-line">
                    @foreach ($cartItems as $item)
                        @php $variantLabel = $item['variant'] ? (collect([$item['variant']->size, $item['variant']->color_name ?? $item['variant']->color])->filter()->implode(' / ') ?: $item['variant']->sku) : null; @endphp
                        <li class="flex items-start gap-3 py-3">
                            <span class="w-14 shrink-0 bg-photo"><x-storefront.product-image :product="$item['product']" sizes="56px" class="aspect-[3/4] object-contain" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold">{{ $item['product']->tr('name') }}</span>
                                @if ($variantLabel)<span class="block text-sm text-ink-muted">{{ $variantLabel }}</span>@endif
                                <span class="block text-sm text-ink-muted">{{ __('Quantité : :quantity', ['quantity' => $item['quantity']]) }}</span>
                            </span>
                            <span class="tnum font-semibold">{{ format_fcfa($item['subtotal']) }}</span>
                        </li>
                    @endforeach
                </ul>

                <dl class="tnum mt-4 grid gap-2">
                    <div class="flex justify-between gap-4"><dt class="text-ink-muted">{{ trans_choice('Sous-total (:count article)|Sous-total (:count articles)', $itemCount) }}</dt><dd>{{ format_fcfa($subtotal) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-muted">{{ __('Taxes (:taxrate %)', ['taxrate' => $taxRate]) }}</dt><dd>{{ format_fcfa($tax) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-muted">{{ __('Livraison') }}</dt><dd>{{ $shipping > 0 ? format_fcfa($shipping) : 'Gratuite' }}</dd></div>
                    <div class="mt-2 flex items-baseline justify-between gap-4 border-t border-ink pt-3"><dt class="text-lg font-bold">{{ __('Total') }}</dt><dd class="text-2xl font-bold" style="font-stretch: 75%">{{ format_fcfa($total) }}</dd></div>
                </dl>

                <x-ui.button type="submit" class="mt-5 w-full" :disabled="$methods->isEmpty()" data-submit>
                    <x-ui.icon name="check" class="size-5" />
                    <span data-label>{{ __('Confirmer et payer :total', ['total' => format_fcfa($total)]) }}</span>
                </x-ui.button>
                <p class="mt-3 text-sm text-ink-muted">{{ __('Cliquez une seule fois : la commande est enregistrée dès la confirmation.') }}</p>
                <p class="mt-3"><a href="{{ route('cart.index') }}" class="inline-flex min-h-11 items-center text-sm underline">{{ __('Modifier mon panier') }}</a></p>
            </aside>
        </form>
    </div>
</x-layouts.storefront>
