<x-layouts.storefront :title="__('Paiement en attente')" robots="noindex,nofollow">
    <div class="wrap py-8 sm:py-12">
        <x-storefront.status-page tone="warning" icon="alert" :title="__('Paiement en attente')">
            <p class="text-lg">{{ __('Nous n\'avons pas encore reçu la confirmation de votre paiement.') }}</p>
            @if ($order)
                <dl class="tnum grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 border-y border-ink py-3">
                    <dt class="text-ink-muted">{{ __('Commande') }}</dt><dd>{{ $order->order_number }}</dd>
                    <dt class="text-ink-muted">{{ __('Montant') }}</dt><dd>{{ format_fcfa($order->total) }}</dd>
                </dl>
            @endif
            <div class="flex flex-col gap-3 sm:flex-row">
                @if ($order)<x-ui.button :href="$order->pendingUrl()">{{ __('Suivre mon paiement') }}</x-ui.button>@endif
                <x-ui.button variant="secondary" :href="route('home')">{{ __('Retour à l\'accueil') }}</x-ui.button>
            </div>
        </x-storefront.status-page>
    </div>
</x-layouts.storefront>
