@php
    use App\Support\OrderLabels;

    $shipping = $order->shipping_address ?? [];
    $cancelled = $order->status === 'cancelled';
    $canPay = $order->payment_method === 'moneyfusion' && $order->payment_status === 'pending' && ! $cancelled;
    $canCancel = $order->status === 'pending';
@endphp

<x-layouts.storefront :title="'Commande '.$order->order_number" robots="noindex,nofollow">
    <x-storefront.account-shell :title="'Commande '.$order->order_number" current="orders">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-12">
            <div>
                <section aria-labelledby="items-title">
                    <h2 id="items-title" class="text-3xl">Articles</h2>
                    <ul class="mt-4 divide-y divide-line border-y border-ink">
                        @foreach ($order->items as $item)
                            <li class="flex items-start gap-4 py-4">
                                @if ($item->product)
                                    <span class="w-16 shrink-0 bg-photo"><x-storefront.product-image :product="$item->product" sizes="64px" class="aspect-[3/4] object-contain" /></span>
                                @endif
                                <span class="min-w-0 flex-1">
                                    <span class="block font-semibold">{{ $item->product_name }}</span>
                                    @if ($item->variant)<span class="block text-sm text-ink-muted">{{ $item->variant->label }}</span>@endif
                                    <span class="tnum block text-sm text-ink-muted">{{ format_fcfa($item->price) }} × {{ $item->quantity }}</span>
                                </span>
                                <span class="tnum font-semibold">{{ format_fcfa($item->total) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <dl class="tnum mt-4 ml-auto grid max-w-sm gap-2">
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">Sous-total</dt><dd>{{ format_fcfa($order->subtotal) }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">Taxes</dt><dd>{{ format_fcfa($order->tax) }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-ink-muted">Livraison</dt><dd>{{ $order->shipping_cost > 0 ? format_fcfa($order->shipping_cost) : 'Gratuite' }}</dd></div>
                        <div class="mt-2 flex items-baseline justify-between gap-4 border-t border-ink pt-3"><dt class="text-lg font-bold">Total</dt><dd class="text-2xl font-bold" style="font-stretch: 75%">{{ format_fcfa($order->total) }}</dd></div>
                    </dl>
                </section>
            </div>

            <aside class="grid gap-6">
                <section class="border border-ink bg-white p-5" aria-labelledby="state-title">
                    <h2 id="state-title" class="text-2xl">État</h2>
                    <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1">
                        <dt class="text-ink-muted">Date</dt><dd>{{ $order->created_at->translatedFormat('j F Y à H:i') }}</dd>
                        <dt class="text-ink-muted">Commande</dt><dd class="font-semibold">{{ OrderLabels::status($order->status) }}</dd>
                        <dt class="text-ink-muted">Paiement</dt><dd class="font-semibold">{{ OrderLabels::payment($order->payment_status) }}</dd>
                        <dt class="text-ink-muted">Mode</dt><dd>{{ \App\Support\PaymentMethods::LABELS[$order->payment_method] ?? $order->payment_method }}</dd>
                        @if ($order->tracking_number)
                            <dt class="text-ink-muted">Suivi</dt>
                            <dd>{{ $order->shipping_carrier }} {{ $order->tracking_number }}
                                @if ($order->tracking_url)<a href="{{ $order->tracking_url }}" target="_blank" rel="noopener noreferrer" class="underline">Suivre le colis<span class="sr-only"> (nouvel onglet)</span></a>@endif
                            </dd>
                        @endif
                    </dl>

                    @if ($canPay)
                        <div class="mt-4 grid gap-3" data-payment-poll data-status-url="{{ $order->paymentStatusUrl() }}">
                            <x-ui.button :href="$order->moneyfusion_payment_url ?: $order->pendingUrl()" class="w-full">Régler cette commande</x-ui.button>
                            <p id="payment-check-message" class="text-sm text-ink-muted" role="status" aria-live="polite">Déjà payé ? Le statut se met à jour tout seul.</p>
                            <x-ui.button type="button" variant="secondary" id="payment-check-button" class="w-full">Vérifier maintenant</x-ui.button>
                        </div>
                    @endif
                </section>

                <section class="border border-ink bg-white p-5" aria-labelledby="ship-title">
                    <h2 id="ship-title" class="text-2xl">Livraison</h2>
                    <p class="mt-3">
                        <strong>{{ trim(($shipping['first_name'] ?? '').' '.($shipping['last_name'] ?? '')) }}</strong><br>
                        {{ $shipping['quartier'] ?? '' }}@if (! empty($shipping['city'])), {{ $shipping['city'] }}@endif<br>
                        {{ $shipping['phone'] ?? $order->customer_phone }}
                    </p>
                    @if ($order->notes)<p class="mt-2 text-sm text-ink-muted">Vos instructions : {{ $order->notes }}</p>@endif
                </section>

                @if ($canCancel)
                    <section aria-labelledby="cancel-title">
                        <h2 id="cancel-title" class="sr-only">Annuler</h2>
                        <x-ui.button type="button" variant="secondary" class="w-full" data-dialog-open="cancel-dialog">Annuler cette commande</x-ui.button>
                    </section>
                @endif
            </aside>
        </div>

        @if ($canCancel)
            <dialog id="cancel-dialog" class="m-auto w-[min(26rem,calc(100%-2rem))] border border-ink bg-white p-5" aria-labelledby="cancel-heading">
                <h2 id="cancel-heading" class="text-2xl">Annuler la commande ?</h2>
                <p class="mt-2 text-ink-muted">Les articles retournent en stock. Cette action ne peut pas être défaite.</p>
                <form method="POST" action="{{ route('account.orders.cancel', $order) }}" class="mt-5 grid gap-2 sm:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <x-ui.button type="button" variant="secondary" data-dialog-close>Garder la commande</x-ui.button>
                    <x-ui.button type="submit">Annuler la commande</x-ui.button>
                </form>
            </dialog>
        @endif
    </x-storefront.account-shell>
</x-layouts.storefront>
