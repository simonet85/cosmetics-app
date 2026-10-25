@php
    $title = $activeCategory?->tr('name') ?? __('La boutique');
    $queryKeys = ['search', 'category', 'tag', 'min_price', 'max_price', 'sort', 'sold_out', 'on_sale', 'capacity'];
    $filtered = collect($queryKeys)->contains(fn ($key) => request()->filled($key));
    $selectedCapacities = (array) ($filters['capacity'] ?? []);
    $check = 'flex min-h-11 cursor-pointer items-center gap-3';

    // Every current choice that the small forms (sort, filters) must keep when they send themselves
    $keep = fn (array $except) => collect(request()->query())->except([...$except, 'page'])->all();
    $hidden = function (array $values, string $prefix = '') use (&$hidden) {
        foreach ($values as $name => $value) {
            $field = $prefix === '' ? $name : $prefix.'['.$name.']';
            if (is_array($value)) { $hidden($value, $field); } else { echo '<input type="hidden" name="'.e($field).'" value="'.e($value).'">'; }
        }
    };
    $sortAndCategoryRoute = request()->routeIs('shop.category') ? route('shop.category', $activeCategory->slug) : route('shop.index');
    $columnClass = [2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4'][$columns];
@endphp

<x-layouts.storefront
    :title="$title"
    :description="$activeCategory ? __('Les produits :name : prix nets en FCFA, composition lisible, livraison et paiement mobile money.', ['name' => $activeCategory->tr('name')]) : __('Tous nos soins : prix nets en FCFA, composition lisible, livraison et paiement mobile money.')"
    :canonical="$activeCategory ? route('shop.category', $activeCategory->slug) : route('shop.index')"
    :robots="$filtered && ! $activeCategory ? 'noindex,follow' : null"
>
    <x-slot:head>
        {!! \App\Support\StructuredData::script(\App\Support\StructuredData::breadcrumbs(array_filter([
            [__('Accueil'), route('home')],
            [__('Boutique'), route('shop.index')],
            $activeCategory ? [$activeCategory->tr('name'), route('shop.category', $activeCategory->slug)] : null,
        ]))) !!}
    </x-slot:head>
    <div class="tpl-beauty" data-template="beauty">
        {{-- Title band with the rayons and their number of products --}}
        <section class="on-dark bg-green text-white" aria-labelledby="shop-title">
            <div class="wrap py-8 text-center sm:py-12">
                <h1 id="shop-title" class="text-4xl sm:text-5xl">{{ $title }}</h1>
                @if ($stripCategories->isNotEmpty())
                    <ul class="mt-6 flex flex-wrap justify-center gap-x-8 gap-y-1" aria-label="{{ __('Les rayons') }}">
                        @foreach ($stripCategories as $rayon)
                            <li>
                                <a href="{{ route('shop.category', $rayon->slug) }}" @if ($activeCategory?->id === $rayon->id) aria-current="page" @endif
                                   class="inline-flex min-h-11 flex-col items-center justify-center px-1 font-semibold hover:underline {{ $activeCategory?->id === $rayon->id ? 'underline decoration-2 underline-offset-8' : '' }}">
                                    <span>{{ $rayon->tr('name') }}</span>
                                    <span class="text-xs font-normal text-white/85">{{ trans_choice(':count produit|:count produits', $rayon->sellable_count, ['count' => $rayon->sellable_count]) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <div class="wrap py-6 sm:py-10">
            <div class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10">
                {{-- Filters: a native disclosure on phones, always open from lg up (see setupFilters) --}}
                <aside class="self-start">
                    <details id="filters" class="border border-ink bg-white lg:border-0">
                        <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-4 font-semibold lg:cursor-default lg:px-0">
                            <span>{{ __('Filtrer et trier') }}</span>
                            <x-ui.icon name="plus" class="size-5 lg:hidden" />
                        </summary>

                        <form method="GET" action="{{ $sortAndCategoryRoute }}" class="grid gap-7 border-t border-line p-4 lg:border-0 lg:px-0">
                            @php $hidden($keep(['min_price', 'max_price', 'capacity', 'on_sale', 'sold_out'])); @endphp

                            <fieldset class="grid gap-3">
                                <legend class="mb-1 font-semibold">{{ __('Filtrer par prix') }}</legend>
                                <x-ui.field :label="__('Minimum')" name="min_price" type="number" inputmode="numeric" min="0" step="1" :value="$filters['min_price'] ?? ''" :placeholder="$priceRange['min']" />
                                <x-ui.field :label="__('Maximum')" name="max_price" type="number" inputmode="numeric" min="0" step="1" :value="$filters['max_price'] ?? ''" :placeholder="$priceRange['max']" />
                            </fieldset>

                            @if ($capacities->isNotEmpty())
                                <fieldset class="grid">
                                    <legend class="mb-1 font-semibold">{{ __('Filtrer par contenance') }}</legend>
                                    @foreach ($capacities as $capacity)
                                        <label class="{{ $check }}">
                                            <input type="checkbox" name="capacity[]" value="{{ $capacity->size }}" class="size-5 accent-green" @checked(in_array($capacity->size, $selectedCapacities, true))>
                                            <span class="flex-1">{{ $capacity->size }}</span>
                                            <span class="tnum text-sm text-ink-muted">{{ $capacity->products_count }}</span>
                                        </label>
                                    @endforeach
                                </fieldset>
                            @endif

                            <fieldset class="grid">
                                <legend class="mb-1 font-semibold">{{ __('Disponibilité') }}</legend>
                                <label class="{{ $check }}"><input type="checkbox" name="on_sale" value="1" class="size-5 accent-green" @checked(! empty($filters['on_sale']))> {{ __('En promotion') }}</label>
                                <label class="{{ $check }}"><input type="checkbox" name="sold_out" value="1" class="size-5 accent-green" @checked(! empty($filters['sold_out']))> {{ __('Inclure les produits épuisés') }}</label>
                            </fieldset>

                            <div class="grid gap-2">
                                <x-ui.button type="submit">{{ __('Appliquer') }}</x-ui.button>
                                @if ($filtered)
                                    <x-ui.button variant="text" :href="$sortAndCategoryRoute" class="justify-center">{{ __('Réinitialiser') }}</x-ui.button>
                                @endif
                            </div>
                        </form>
                    </details>

                    @if ($topRated->isNotEmpty())
                        <section class="mt-8 hidden lg:block" aria-labelledby="top-rated">
                            <h2 id="top-rated" class="text-base font-semibold">{{ __('Produits les mieux notés') }}</h2>
                            <ul class="mt-3 divide-y divide-line">
                                @foreach ($topRated as $rated)
                                    <li class="flex items-center gap-3 py-3">
                                        <a href="{{ route('products.show', $rated->slug) }}" tabindex="-1" aria-hidden="true" class="w-16 shrink-0 bg-photo">
                                            <x-storefront.product-image :product="$rated" sizes="64px" class="aspect-square object-contain" />
                                        </a>
                                        <div class="text-sm">
                                            <a href="{{ route('products.show', $rated->slug) }}" class="font-semibold hover:underline">{{ $rated->tr('name') }}</a>
                                            <p class="tnum text-ink-muted">{{ __(':reviewaverage sur 5', ['reviewaverage' => number_format($rated->rating_average, 1, ',', '')]) }}</p>
                                            <x-ui.price :amount="$rated->final_price" />
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </aside>

                <div>
                    <nav aria-label="{{ __('Fil d\'Ariane') }}" class="flex flex-wrap items-center text-sm text-ink-muted">
                        <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center underline">{{ __('Accueil') }}</a>
                        <span aria-hidden="true" class="mx-2">/</span>
                        @if ($activeCategory)
                            <a href="{{ route('shop.index') }}" class="inline-flex min-h-11 items-center underline">{{ __('Boutique') }}</a>
                            <span aria-hidden="true" class="mx-2">/</span>
                            <span aria-current="page" class="text-ink">{{ $activeCategory->tr('name') }}</span>
                        @else
                            <span aria-current="page" class="text-ink">{{ __('Boutique') }}</span>
                        @endif
                    </nav>

                    {{-- Toolbar: how many per page, how many columns, sort order --}}
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-x-6 gap-y-2 border-b border-line pb-3 text-sm">
                        <p role="status" aria-live="polite" class="tnum">{{ trans_choice(':count produit|:count produits', $products->total(), ['count' => $products->total()]) }}</p>

                        <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                            <nav aria-label="{{ __('Produits par page') }}" class="flex items-center gap-1">
                                <span aria-hidden="true">{{ __('Afficher') }} :</span>
                                @foreach ($perPageOptions as $option)
                                    <a href="{{ request()->fullUrlWithQuery(['per_page' => $option, 'page' => null]) }}" @if ($perPage === $option) aria-current="true" @endif
                                       class="tnum inline-flex min-h-11 min-w-9 items-center justify-center px-1 {{ $perPage === $option ? 'font-bold underline decoration-2 underline-offset-8' : 'hover:underline' }}">
                                        {{ $option }}<span class="sr-only"> {{ __('produits par page') }}</span>
                                    </a>
                                @endforeach
                            </nav>

                            <nav aria-label="{{ __('Colonnes') }}" class="hidden items-center gap-1 lg:flex">
                                @foreach ([2, 3, 4] as $count)
                                    <a href="{{ request()->fullUrlWithQuery(['cols' => $count, 'page' => null]) }}" @if ($columns === $count) aria-current="true" @endif
                                       class="tnum inline-flex min-h-11 min-w-9 items-center justify-center border {{ $columns === $count ? 'border-ink font-bold' : 'border-transparent hover:border-line' }}">
                                        {{ $count }}<span class="sr-only"> {{ __('colonnes') }}</span>
                                    </a>
                                @endforeach
                            </nav>

                            <form method="GET" action="{{ $sortAndCategoryRoute }}" class="flex items-center gap-2">
                                @php $hidden($keep(['sort'])); @endphp
                                <label for="sort" class="sr-only">{{ __('Trier par') }}</label>
                                <select id="sort" name="sort" data-autosubmit class="min-h-11 border-b border-field bg-white px-2 text-sm">
                                    @foreach ($sorts as $value => $label)
                                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <noscript><x-ui.button type="submit" variant="secondary">{{ __('Appliquer') }}</x-ui.button></noscript>
                            </form>
                        </div>
                    </div>

                    @if ($products->isEmpty())
                        <div class="mt-6">
                            <x-ui.empty-state :title="__('Aucun produit ne correspond')" :text="__('Essayez de retirer un filtre ou de chercher autre chose.')">
                                <x-ui.button :href="route('shop.index')">{{ __('Voir toute la boutique') }}</x-ui.button>
                            </x-ui.empty-state>
                        </div>
                    @else
                        <h2 class="sr-only">{{ __('Produits') }}</h2>
                        <ul class="mt-6 grid grid-cols-2 gap-x-4 gap-y-10 {{ $columnClass }}">
                            @foreach ($products as $product)
                                <li><x-storefront.product-tile :product="$product" :in-wishlist="in_array($product->id, $wishlistIds, true)" /></li>
                            @endforeach
                        </ul>

                        {{ $products->links('storefront.pagination') }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.storefront>
