@php
    $threshold = \App\Support\Shipping::freeThreshold();   // null when shipping is always free
    $taxRate = rtrim(rtrim(number_format((float) getSetting('tax_rate', '15'), 2, ',', ''), '0'), ',');
    $payments = \App\Support\PaymentMethods::acceptedLabels();
    $remaining = $threshold === null ? 0 : max(0, $threshold - $subtotal);
    $progress = $threshold ? min(100, (int) round($subtotal / $threshold * 100)) : 100;
    $itemCount = collect($cartItems)->sum('quantity');
@endphp

<x-layouts.storefront :title="__('Panier')" :description="__('Votre panier Glowing Cosmetics.')" robots="noindex,follow">
    <div class="wrap py-6 sm:py-10"
         data-cart data-threshold="{{ $threshold ?? 0 }}"
         data-update-url="{{ route('cart.update') }}" data-remove-url="{{ route('cart.remove') }}" data-clear-url="{{ route('cart.clear') }}">
        <nav aria-label="{{ __('Fil d\'Ariane') }}" class="flex flex-wrap items-center text-sm text-ink-muted">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center underline">{{ __('Accueil') }}</a>
            <span aria-hidden="true" class="mx-2">/</span>
            <span aria-current="page" class="text-ink">{{ __('Panier') }}</span>
        </nav>

        <h1 class="mt-2 text-4xl uppercase sm:text-5xl">{{ __('Votre panier') }}</h1>

        @if (count($cartItems) === 0)
            <x-ui.empty-state class="mt-8" :title="__('Votre panier est vide')" :text="__('Ajoutez un produit depuis la boutique pour le retrouver ici.')">
                <x-ui.button :href="route('shop.index')">{{ __('Voir la boutique') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <div class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-12">
                <div>
                    @if ($threshold !== null)
                    <div class="mb-4 border border-ink bg-mint p-4" data-free-shipping>
                        <p class="font-semibold" data-free-shipping-text>
                            @if ($remaining > 0)
                                {{ __('Plus que :remaining pour la livraison gratuite.', ['remaining' => format_fcfa($remaining)]) }}
                            @else
                                {{ __('La livraison est gratuite pour cette commande.') }}
                            @endif
                        </p>
                        <div class="mt-2 h-2 bg-white" aria-hidden="true"><div class="h-2 bg-green" style="width: {{ $progress }}%" data-free-shipping-bar></div></div>
                    </div>
                    @endif

                    <ul class="divide-y divide-line border-y border-ink" aria-label="{{ __('Articles du panier') }}">
                        @foreach ($cartItems as $item)
                            @php
                                $product = $item['product'];
                                $variant = $item['variant'];
                                $max = max(1, $variant ? $variant->stock : $product->stock);
                                $variantLabel = $variant ? (collect([$variant->size, $variant->color_name ?? $variant->color])->filter()->implode(' / ') ?: $variant->sku) : null;
                            @endphp
                            <li class="grid grid-cols-[5rem_1fr] gap-x-4 gap-y-3 py-4 sm:grid-cols-[6rem_1fr]" data-cart-line="{{ $item['id'] }}">
                                <a href="{{ route('products.show', $product->slug) }}" tabindex="-1" aria-hidden="true" class="bg-photo">
                                    <x-storefront.product-image :product="$product" sizes="96px" class="aspect-[3/4] object-contain" />
                                </a>

                                <div class="grid content-start gap-1">
                                    <h2 class="text-xl uppercase leading-tight">
                                        <a href="{{ route('products.show', $product->slug) }}" class="inline-block py-1 hover:underline">{{ $product->name }}</a>
                                    </h2>
                                    @if ($variantLabel)<p class="text-sm text-ink-muted">{{ $variantLabel }}</p>@endif
                                    <p class="tnum text-sm text-ink-muted">{{ __(':price l\'unité', ['price' => format_fcfa($item['price'])]) }}</p>
                                </div>

                                <div class="col-span-2 flex flex-wrap items-center justify-between gap-3 sm:col-span-1 sm:col-start-2">
                                    <div class="flex items-center border border-ink" role="group" aria-label="{{ __('Quantité de :name', ['name' => $product->name]) }}">
                                        <button type="button" data-cart-step="-1" aria-label="{{ __('Retirer une unité') }}" class="inline-flex size-12 cursor-pointer items-center justify-center hover:bg-mint">
                                            <x-ui.icon name="minus" class="size-5" />
                                        </button>
                                        <input type="number" value="{{ $item['quantity'] }}" min="1" max="{{ $max }}" inputmode="numeric" data-cart-qty data-prev="{{ $item['quantity'] }}"
                                               aria-label="{{ __('Quantité de :name', ['name' => $product->name]) }}"
                                               class="tnum h-12 w-14 border-x border-ink text-center text-lg [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                                        <button type="button" data-cart-step="1" aria-label="{{ __('Ajouter une unité') }}" class="inline-flex size-12 cursor-pointer items-center justify-center hover:bg-mint">
                                            <x-ui.icon name="plus" class="size-5" />
                                        </button>
                                    </div>

                                    <p class="tnum text-2xl font-bold" style="font-stretch: 75%" data-line-total>{{ format_fcfa($item['subtotal']) }}</p>

                                    <button type="button" data-cart-remove class="inline-flex min-h-11 cursor-pointer items-center gap-1 text-sm underline">
                                        {{ __('Retirer') }}<span class="sr-only"> {{ __(':name du panier', ['name' => $product->name]) }}</span>
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                        <x-ui.button variant="text" :href="route('shop.index')">{{ __('Continuer mes achats') }}</x-ui.button>
                        <button type="button" data-dialog-open="clear-dialog" class="inline-flex min-h-11 cursor-pointer items-center text-sm underline">{{ __('Vider le panier') }}</button>
                    </div>
                </div>

                <aside class="border border-ink bg-white p-5 lg:sticky lg:top-24" aria-labelledby="summary-title">
                    <h2 id="summary-title" class="text-2xl">{{ __('Résumé') }}</h2>
                    <dl class="tnum mt-4 grid gap-2">
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">{{ trans_choice('Sous-total (:count article)|Sous-total (:count articles)', $itemCount) }}</dt><dd data-sum="subtotal">{{ format_fcfa($subtotal) }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">{{ __('Taxes (:taxrate %)', ['taxrate' => $taxRate]) }}</dt><dd data-sum="tax">{{ format_fcfa($tax) }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">{{ __('Livraison') }}</dt><dd data-sum="shipping">{{ $shipping > 0 ? format_fcfa($shipping) : 'Gratuite' }}</dd></div>
                        <div class="mt-2 flex items-baseline justify-between gap-4 border-t border-ink pt-3"><dt class="text-lg font-bold">{{ __('Total') }}</dt><dd class="text-2xl font-bold" style="font-stretch: 75%" data-sum="total">{{ format_fcfa($total) }}</dd></div>
                    </dl>

                    <x-ui.button :href="route('checkout.index')" class="mt-5 w-full">{{ __('Passer la commande') }}</x-ui.button>

                    <p class="mt-4 flex items-start gap-2 text-sm text-ink-muted">
                        <x-ui.icon name="smartphone" class="mt-0.5 size-4 shrink-0" />
                        <span>{{ __('Paiement : :payments', ['payments' => implode(', ', $payments)]) }}</span>
                    </p>
                    <p class="mt-3 text-sm text-ink-muted">
                        @auth
                            {{ __('Votre panier est enregistré avec votre compte.') }}
                        @else
                            {!! __(':link pour retrouver ce panier sur tous vos appareils.', [
                                'link' => '<a href="'.e(route('login')).'" class="inline-flex min-h-11 items-center underline">'.e(__('Connectez-vous')).'</a>',
                            ]) !!}
                        @endauth
                    </p>
                </aside>
            </div>

            <dialog id="clear-dialog" class="m-auto w-[min(26rem,calc(100%-2rem))] border border-ink bg-white p-5" aria-labelledby="clear-title">
                <h2 id="clear-title" class="text-2xl">{{ __('Vider le panier ?') }}</h2>
                <p class="mt-2 text-ink-muted">{{ __('Tous les articles seront retirés.') }}</p>
                <div class="mt-5 grid gap-2 sm:grid-cols-2">
                    <x-ui.button type="button" variant="secondary" data-dialog-close>{{ __('Annuler') }}</x-ui.button>
                    <x-ui.button type="button" data-cart-clear>{{ __('Vider le panier') }}</x-ui.button>
                </div>
            </dialog>
        @endif
    </div>
</x-layouts.storefront>
