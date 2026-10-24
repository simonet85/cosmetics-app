<x-layouts.storefront :title="__('Mes commandes')" robots="noindex,nofollow">
    <x-storefront.account-shell :title="__('Mes commandes')" current="orders">
        @if ($orders->isEmpty())
            <x-ui.empty-state :title="__('Aucune commande pour le moment')" :text="__('Vos commandes apparaîtront ici dès la première.')">
                <x-ui.button :href="route('shop.index')">{{ __('Voir la boutique') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <x-storefront.order-list :orders="$orders" />
            {{ $orders->links('storefront.pagination') }}
        @endif
    </x-storefront.account-shell>
</x-layouts.storefront>
