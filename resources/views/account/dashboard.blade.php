<x-layouts.storefront title="Mon compte" robots="noindex,nofollow">
    <x-storefront.account-shell :title="'Bonjour '.$user->first_name" current="dashboard">
        <dl class="tnum grid grid-cols-2 gap-px border border-ink bg-ink lg:grid-cols-4">
            <div class="bg-white p-4"><dt class="label-caps text-ink-muted">Commandes</dt><dd class="mt-1 text-4xl font-bold" style="font-stretch: 75%">{{ $totalOrders }}</dd></div>
            <div class="bg-white p-4"><dt class="label-caps text-ink-muted">En attente</dt><dd class="mt-1 text-4xl font-bold" style="font-stretch: 75%">{{ $pendingOrders }}</dd></div>
            <div class="bg-white p-4"><dt class="label-caps text-ink-muted">Livrées</dt><dd class="mt-1 text-4xl font-bold" style="font-stretch: 75%">{{ $completedOrders }}</dd></div>
            <div class="bg-white p-4"><dt class="label-caps text-ink-muted">Total payé</dt><dd class="mt-1 text-3xl font-bold" style="font-stretch: 75%">{{ format_fcfa($totalSpent) }}</dd></div>
        </dl>

        <section class="mt-10" aria-labelledby="recent-title">
            <div class="flex items-end justify-between gap-4">
                <h2 id="recent-title" class="text-3xl">Dernières commandes</h2>
                @if ($totalOrders > 0)<x-ui.button variant="text" :href="route('account.orders')">Toutes mes commandes</x-ui.button>@endif
            </div>

            @if ($recentOrders->isEmpty())
                <x-ui.empty-state class="mt-4" title="Aucune commande pour le moment" text="Vos commandes apparaîtront ici.">
                    <x-ui.button :href="route('shop.index')">Voir la boutique</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-storefront.order-list :orders="$recentOrders" />
            @endif
        </section>

        <div class="mt-10 grid gap-3 sm:grid-cols-2">
            <x-ui.button variant="secondary" :href="route('wishlist.index')">Mes favoris</x-ui.button>
            <x-ui.button variant="secondary" :href="route('account.profile')">Modifier mon profil</x-ui.button>
        </div>
    </x-storefront.account-shell>
</x-layouts.storefront>
