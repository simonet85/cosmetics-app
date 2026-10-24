<x-layouts.storefront :title="__('Échec du paiement')" robots="noindex,nofollow">
    <div class="wrap py-8 sm:py-12">
        <x-storefront.status-page tone="danger" icon="alert" :title="__('Échec du paiement')">
            <p class="text-lg">{{ __('Votre paiement n\'a pas pu être traité. Vous pouvez réessayer ou choisir un autre mode de paiement.') }}</p>
            @if ($order)
                <dl class="tnum grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 border-y border-ink py-3">
                    <dt class="text-ink-muted">{{ __('Commande') }}</dt><dd>{{ $order->order_number }}</dd>
                    <dt class="text-ink-muted">{{ __('Montant') }}</dt><dd>{{ format_fcfa($order->total) }}</dd>
                </dl>
            @endif
            <div class="flex flex-col gap-3 sm:flex-row">
                @if ($order)<x-ui.button :href="route('checkout.index')">{{ __('Réessayer le paiement') }}</x-ui.button>@endif
                <x-ui.button variant="secondary" :href="route('home')">{{ __('Retour à l\'accueil') }}</x-ui.button>
            </div>
        </x-storefront.status-page>
    </div>
</x-layouts.storefront>
