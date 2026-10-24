<x-layouts.storefront title="Paiement en cours" description="Suivi de votre paiement." robots="noindex,nofollow">
    <div class="wrap py-8 sm:py-12" data-payment-status data-status-url="{{ $statusUrl }}">
        {{-- Waiting --}}
        <div id="pending-box" class="{{ $failed ? 'hidden' : '' }}">
            <x-storefront.status-page tone="warning" icon="alert" title="Paiement en cours">
                <p>Nous attendons la confirmation de votre paiement par MoneyFusion. Cette page se met à jour toute seule : vous n'avez rien à faire.</p>
                <p id="pending-message" class="text-sm text-ink-muted" role="status" aria-live="polite">Vérification en cours…</p>

                <dl class="tnum grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 border-y border-ink py-3">
                    <dt class="text-ink-muted">Commande</dt><dd>{{ $order->order_number }}</dd>
                    <dt class="text-ink-muted">Montant</dt><dd>{{ format_fcfa($order->total) }}</dd>
                    <dt class="text-ink-muted">E-mail</dt><dd class="break-all">{{ $order->customer_email }}</dd>
                </dl>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <x-ui.button type="button" id="check-now">Vérifier maintenant</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('home')">Retour à l'accueil</x-ui.button>
                </div>
            </x-storefront.status-page>
        </div>

        {{-- Failed / cancelled --}}
        <div id="failed-box" class="{{ $failed ? '' : 'hidden' }}">
            <x-storefront.status-page tone="danger" icon="alert" title="Le paiement n'a pas abouti">
                <p>Votre commande {{ $order->order_number }} a été annulée et aucun montant ne vous a été débité. Si vous avez tout de même payé, votre commande sera rétablie automatiquement dès confirmation.</p>
                <div><x-ui.button :href="route('checkout.index')">Réessayer le paiement</x-ui.button></div>
            </x-storefront.status-page>
        </div>
    </div>
</x-layouts.storefront>
