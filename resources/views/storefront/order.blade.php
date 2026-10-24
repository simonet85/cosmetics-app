@php
    use App\Support\OrderLabels;

    $paid = $order->payment_status === 'paid';
    $cancelled = $order->status === 'cancelled' || in_array($order->payment_status, ['failed', 'refunded'], true);
    $method = $order->payment_method;
    $shipping = $order->shipping_address ?? [];
    $owner = auth()->check() && $order->user_id === auth()->id();

    if ($cancelled) {
        [$title, $lead, $tone, $icon] = [__('Commande annulée'), __('Cette commande n\'est plus active. Aucun montant n\'est dû pour elle.'), 'danger', 'alert'];
    } elseif ($paid) {
        [$title, $lead, $tone, $icon] = [__('Paiement reçu, merci'), __('Votre commande :number est confirmée.', ['number' => $order->order_number]), 'success', 'check'];
    } elseif ($method === 'cash_on_delivery') {
        [$title, $lead, $tone, $icon] = [__('Commande enregistrée'), __('Vous réglez :amount en espèces à la livraison. Préparez le montant exact.', ['amount' => format_fcfa($order->total)]), 'success', 'check'];
    } elseif ($method === 'bank_transfer') {
        [$title, $lead, $tone, $icon] = [__('Commande enregistrée'), __('Elle sera préparée dès réception de votre virement de :amount.', ['amount' => format_fcfa($order->total)]), 'warning', 'alert'];
    } else {
        [$title, $lead, $tone, $icon] = [__('Commande enregistrée'), __('Nous attendons la confirmation de votre paiement.'), 'warning', 'alert'];
    }
@endphp

<x-layouts.storefront :title="$title" :description="__('Récapitulatif de votre commande Glowing Cosmetics.')" robots="noindex,nofollow">
    <div class="wrap py-8 sm:py-12">
        <x-storefront.status-page :tone="$tone" :icon="$icon" :title="$title">
            <p class="text-lg">{{ $lead }}</p>
            <p>{{ __('Numéro de commande :') }} <strong class="tnum">{{ $order->order_number }}</strong></p>

            @if ($method === 'bank_transfer' && ! $paid && ! $cancelled)
                <p>{!! __('Pour obtenir les coordonnées bancaires, :link en indiquant ce numéro de commande.', [
                    'link' => '<a href="'.e(route('pages.contact')).'" class="font-semibold underline">'.e(__('contactez-nous')).'</a>',
                ]) !!}</p>
            @endif
            @if (! $paid && ! $cancelled && $method === 'moneyfusion')
                <x-ui.button :href="$order->pendingUrl()" class="w-full sm:w-auto">{{ __('Suivre mon paiement') }}</x-ui.button>
            @endif
        </x-storefront.status-page>

        <div class="mx-auto mt-8 grid max-w-4xl gap-8">
            <section aria-labelledby="items-title">
                <h2 id="items-title" class="text-3xl">{{ __('Votre commande') }}</h2>
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
                    <div class="flex justify-between gap-4"><dt class="text-ink-muted">{{ __('Sous-total') }}</dt><dd>{{ format_fcfa($order->subtotal) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-muted">{{ __('Taxes') }}</dt><dd>{{ format_fcfa($order->tax) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-ink-muted">{{ __('Livraison') }}</dt><dd>{{ $order->shipping_cost > 0 ? format_fcfa($order->shipping_cost) : 'Gratuite' }}</dd></div>
                    <div class="mt-2 flex items-baseline justify-between gap-4 border-t border-ink pt-3"><dt class="text-lg font-bold">{{ __('Total') }}</dt><dd class="text-2xl font-bold" style="font-stretch: 75%">{{ format_fcfa($order->total) }}</dd></div>
                </dl>
            </section>

            <div class="grid gap-8 sm:grid-cols-2">
                <section aria-labelledby="info-title">
                    <h2 id="info-title" class="text-3xl">{{ __('Informations') }}</h2>
                    <dl class="mt-4 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1">
                        <dt class="text-ink-muted">{{ __('Date') }}</dt><dd>{{ $order->created_at->translatedFormat('j F Y à H:i') }}</dd>
                        <dt class="text-ink-muted">{{ __('Commande') }}</dt><dd>{{ OrderLabels::status($order->status) }}</dd>
                        <dt class="text-ink-muted">{{ __('Paiement') }}</dt><dd>{{ OrderLabels::payment($order->payment_status) }} ({{ \App\Support\PaymentMethods::label($method) }})</dd>
                        <dt class="text-ink-muted">{{ __('E-mail') }}</dt><dd class="break-all">{{ $order->customer_email }}</dd>
                        <dt class="text-ink-muted">{{ __('Téléphone') }}</dt><dd>{{ $order->customer_phone }}</dd>
                        @if ($order->tracking_number)
                            <dt class="text-ink-muted">{{ __('Suivi') }}</dt>
                            <dd>
                                {{ $order->shipping_carrier }} {{ $order->tracking_number }}
                                @if ($order->tracking_url)<a href="{{ $order->tracking_url }}" target="_blank" rel="noopener noreferrer" class="underline">{{ __('Suivre le colis') }}<span class="sr-only"> {{ __('(nouvel onglet)') }}</span></a>@endif
                            </dd>
                        @endif
                    </dl>
                </section>

                <section aria-labelledby="address-title">
                    <h2 id="address-title" class="text-3xl">{{ __('Livraison') }}</h2>
                    <p class="mt-4">
                        <strong>{{ trim(($shipping['first_name'] ?? '').' '.($shipping['last_name'] ?? '')) }}</strong><br>
                        {{ $shipping['quartier'] ?? '' }}@if (! empty($shipping['city'])), {{ $shipping['city'] }}@endif<br>
                        {{ $shipping['phone'] ?? '' }}
                    </p>
                    @if ($order->notes)<p class="mt-2 text-sm text-ink-muted">{{ __('Vos instructions : :notes', ['notes' => $order->notes]) }}</p>@endif
                </section>
            </div>

            <section aria-labelledby="next-title" class="border border-ink bg-white p-5">
                <h2 id="next-title" class="text-2xl">{{ __('Et maintenant ?') }}</h2>
                <ul class="mt-3 grid gap-2">
                    @if ($paid)
                        <li class="flex items-start gap-2"><x-ui.icon name="check" class="mt-0.5 size-5 shrink-0 text-green-deep" /> {{ __('Un e-mail de confirmation a été envoyé à :customeremail.', ['customeremail' => $order->customer_email]) }}</li>
                    @endif
                    @if ($owner)
                        <li class="flex items-start gap-2"><x-ui.icon name="check" class="mt-0.5 size-5 shrink-0 text-green-deep" /> <span>{!! __('Retrouvez cette commande dans :link.', [
                            'link' => '<a href="'.e(route('account.orders')).'" class="font-semibold underline">'.e(__('Mes commandes')).'</a>',
                        ]) !!}</span></li>
                    @else
                        <li class="flex items-start gap-2"><x-ui.icon name="check" class="mt-0.5 size-5 shrink-0 text-green-deep" /> {{ __('Gardez cette page : son adresse vous permet de revoir votre commande.') }}</li>
                    @endif
                    <li class="flex items-start gap-2"><x-ui.icon name="check" class="mt-0.5 size-5 shrink-0 text-green-deep" /> <span>{!! __('Une question ? :link avec le numéro de commande.', [
                        'link' => '<a href="'.e(route('pages.contact')).'" class="font-semibold underline">'.e(__('Écrivez-nous')).'</a>',
                    ]) !!}</span></li>
                </ul>
            </section>

            <div class="flex flex-col gap-3 sm:flex-row">
                <x-ui.button :href="route('shop.index')">{{ __('Continuer mes achats') }}</x-ui.button>
                <x-ui.button variant="secondary" :href="route('home')">{{ __('Retour à l\'accueil') }}</x-ui.button>
            </div>
        </div>
    </div>
</x-layouts.storefront>
