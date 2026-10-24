<x-layouts.storefront title="Mes commandes" robots="noindex,nofollow">
    <x-storefront.account-shell title="Mes commandes" current="orders">
        @if ($orders->isEmpty())
            <x-ui.empty-state title="Aucune commande pour le moment" text="Vos commandes apparaîtront ici dès la première.">
                <x-ui.button :href="route('shop.index')">Voir la boutique</x-ui.button>
            </x-ui.empty-state>
        @else
            <x-storefront.order-list :orders="$orders" />
            {{ $orders->links('storefront.pagination') }}
        @endif
    </x-storefront.account-shell>
</x-layouts.storefront>
