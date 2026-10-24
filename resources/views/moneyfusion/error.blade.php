<x-layouts.storefront title="Erreur de paiement" robots="noindex,nofollow">
    <div class="wrap py-8 sm:py-12">
        <x-storefront.status-page tone="danger" icon="alert" title="Erreur de paiement">
            <p class="text-lg">{{ $message ?? 'Une erreur est survenue lors du traitement de votre paiement. Veuillez réessayer.' }}</p>
            <div class="flex flex-col gap-3 sm:flex-row">
                <x-ui.button :href="route('checkout.index')">Retour au paiement</x-ui.button>
                <x-ui.button variant="secondary" :href="route('home')">Retour à l'accueil</x-ui.button>
            </div>
        </x-storefront.status-page>
    </div>
</x-layouts.storefront>
