<x-layouts.storefront :title="__('Paiement réussi')" robots="noindex,nofollow">
    <div class="wrap py-8 sm:py-12">
        <x-storefront.status-page tone="success" icon="check" :title="__('Paiement réussi')">
            <p class="text-lg">{{ __('Votre paiement a bien été effectué.') }}</p>
            @if ($order)
                <dl class="tnum grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 border-y border-ink py-3">
                    <dt class="text-ink-muted">{{ __('Commande') }}</dt><dd>{{ $order->order_number }}</dd>
                    <dt class="text-ink-muted">{{ __('Montant') }}</dt><dd>{{ format_fcfa($order->total) }}</dd>
                    <dt class="text-ink-muted">{{ __('E-mail') }}</dt><dd class="break-all">{{ $order->customer_email }}</dd>
                </dl>
            @endif
            <div class="flex flex-col gap-3 sm:flex-row">
                @if ($order)<x-ui.button :href="$order->successUrl()">{{ __('Voir ma commande') }}</x-ui.button>@endif
                <x-ui.button variant="secondary" :href="route('home')">{{ __('Retour à l\'accueil') }}</x-ui.button>
            </div>
        </x-storefront.status-page>
    </div>
</x-layouts.storefront>
