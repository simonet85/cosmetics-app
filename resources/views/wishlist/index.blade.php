<x-layouts.storefront title="Mes favoris" description="Les produits que vous avez mis de côté." robots="noindex,follow">
    <div class="wrap py-6 sm:py-10">
        <nav aria-label="Fil d'Ariane" class="flex flex-wrap items-center text-sm text-ink-muted">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center underline">Accueil</a>
            <span aria-hidden="true" class="mx-2">/</span>
            <span aria-current="page" class="text-ink">Mes favoris</span>
        </nav>

        <h1 class="mt-2 text-4xl uppercase sm:text-5xl">Mes favoris</h1>

        @if ($wishlistItems->isEmpty())
            <x-ui.empty-state class="mt-8" title="Vous n'avez pas encore de favoris" text="Touchez le cœur sur une fiche produit pour le retrouver ici.">
                <x-ui.button :href="route('shop.index')">Voir la boutique</x-ui.button>
            </x-ui.empty-state>
        @else
            <p class="mt-2 text-ink-muted">{{ $wishlistItems->count() }} {{ $wishlistItems->count() > 1 ? 'produits' : 'produit' }}</p>

            <ul class="mt-6 divide-y divide-line border-y border-ink" data-wishlist-list data-remove-url="{{ route('wishlist.remove') }}">
                @foreach ($wishlistItems as $product)
                    <li class="grid grid-cols-[5rem_1fr] items-center gap-x-4 gap-y-3 py-4 sm:grid-cols-[6rem_1fr_auto_auto] sm:gap-x-6">
                        <a href="{{ route('products.show', $product->slug) }}" tabindex="-1" aria-hidden="true" class="bg-photo">
                            <x-storefront.product-image :product="$product" sizes="96px" class="aspect-[3/4] object-contain" />
                        </a>
                        <div>
                            <h2 class="text-xl uppercase leading-tight">
                                <a href="{{ route('products.show', $product->slug) }}" class="inline-block py-1 hover:underline">{{ $product->name }}</a>
                            </h2>
                            @if ($product->categories->isNotEmpty())<p class="text-sm text-ink-muted">{{ $product->categories->first()->name }}</p>@endif
                            @if ($product->stock <= 0)<p class="mt-1 text-sm font-semibold text-danger">Épuisé pour le moment</p>@endif
                        </div>
                        <x-ui.price :amount="$product->final_price" :original="$product->discount_price > 0 ? $product->price : null" size="lg" class="col-start-2 sm:col-start-auto" />
                        <div class="col-span-2 grid gap-2 sm:col-span-1 sm:w-56">
                            <x-storefront.add-to-cart :product="$product" />
                            <button type="button" data-wishlist-remove="{{ $product->id }}" class="inline-flex min-h-11 cursor-pointer items-center justify-center text-sm underline">
                                Retirer<span class="sr-only"> {{ $product->name }} des favoris</span>
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.storefront>
