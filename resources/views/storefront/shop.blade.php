@php
    $title = $activeCategory?->name ?? __('La boutique');
    $queryKeys = ['search', 'category', 'tag', 'min_price', 'max_price', 'sort', 'sold_out'];
    $filtered = collect($queryKeys)->contains(fn ($key) => request()->filled($key));
    $without = fn (array $keys) => route('shop.index', collect(request()->query())->except([...$keys, 'page'])->all());

    // One chip per active filter, each one removes itself
    $chips = collect();
    if ($categorySlug && $activeCategory && ! request()->routeIs('shop.category')) { $chips->push(['label' => $activeCategory->name, 'url' => $without(['category'])]); }
    if (! empty($filters['tag'])) { $chips->push(['label' => '#'.($tags->firstWhere('slug', $filters['tag'])?->name ?? $filters['tag']), 'url' => $without(['tag'])]); }
    if (! empty($filters['search'])) { $chips->push(['label' => '« '.$filters['search'].' »', 'url' => $without(['search'])]); }
    if (isset($filters['min_price'])) { $chips->push(['label' => __('dès :amount', ['amount' => format_fcfa($filters['min_price'])]), 'url' => $without(['min_price'])]); }
    if (isset($filters['max_price'])) { $chips->push(['label' => __('jusqu\'à :amount', ['amount' => format_fcfa($filters['max_price'])]), 'url' => $without(['max_price'])]); }
    if (! empty($filters['sold_out'])) { $chips->push(['label' => __('épuisés inclus'), 'url' => $without(['sold_out'])]); }
    if ($sort !== 'latest') { $chips->push(['label' => $sorts[$sort], 'url' => $without(['sort'])]); }

    $radio = 'flex min-h-11 cursor-pointer items-center gap-3';
@endphp

<x-layouts.storefront
    :title="$title"
    :description="$activeCategory ? __('Les produits :name : prix nets en FCFA, composition lisible, livraison et paiement mobile money.', ['name' => $activeCategory->name]) : __('Tous nos soins : prix nets en FCFA, composition lisible, livraison et paiement mobile money.')"
    :canonical="$activeCategory ? route('shop.category', $activeCategory->slug) : route('shop.index')"
    :robots="$filtered && ! $activeCategory ? 'noindex,follow' : null"
>
    <div class="wrap py-6 sm:py-10">
        <nav aria-label="{{ __('Fil d\'Ariane') }}" class="flex flex-wrap items-center text-sm text-ink-muted">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center underline">{{ __('Accueil') }}</a>
            <span aria-hidden="true" class="mx-2">/</span>
            @if ($activeCategory)
                <a href="{{ route('shop.index') }}" class="inline-flex min-h-11 items-center underline">{{ __('Boutique') }}</a>
                <span aria-hidden="true" class="mx-2">/</span>
                <span aria-current="page" class="text-ink">{{ $activeCategory->name }}</span>
            @else
                <span aria-current="page" class="text-ink">{{ __('Boutique') }}</span>
            @endif
        </nav>

        <h1 class="mt-2 text-4xl uppercase sm:text-5xl">{{ $title }}</h1>
        <p class="mt-2 text-ink-muted" role="status" aria-live="polite">
            {{ $products->total() }} {{ $products->total() > 1 ? 'produits' : 'produit' }}
        </p>

        <div class="mt-6 grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10">
            {{-- Filters: a native disclosure on phones (no script needed), always open from lg up --}}
            <details id="filters" class="self-start border border-ink bg-white lg:border-0">
                <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-4 font-semibold lg:cursor-default lg:px-0">
                    <span class="flex items-center gap-2">
                        {{ __('Filtrer et trier') }}
                        @if ($chips->isNotEmpty())<x-ui.badge tone="ink">{{ $chips->count() }}</x-ui.badge>@endif
                    </span>
                    <x-ui.icon name="plus" class="size-5 lg:hidden" />
                </summary>

                <form method="GET" action="{{ route('shop.index') }}" class="grid gap-6 border-t border-line p-4 lg:border-0 lg:px-0">
                    <x-ui.field :label="__('Rechercher')" name="search" type="search" :value="$filters['search'] ?? ''" autocomplete="off" />

                    <fieldset class="grid">
                        <legend class="label-caps mb-1 text-ink-muted">{{ __('Rayon') }}</legend>
                        <label class="{{ $radio }}"><input type="radio" name="category" value="" class="size-5 accent-green" @checked(! $categorySlug)> {{ __('Tous les rayons') }}</label>
                        @foreach ($categories as $cat)
                            <label class="{{ $radio }}"><input type="radio" name="category" value="{{ $cat->slug }}" class="size-5 accent-green" @checked($categorySlug === $cat->slug)> {{ $cat->name }}</label>
                        @endforeach
                    </fieldset>

                    @if ($tags->isNotEmpty())
                        <fieldset class="grid">
                            <legend class="label-caps mb-1 text-ink-muted">{{ __('Étiquette') }}</legend>
                            <label class="{{ $radio }}"><input type="radio" name="tag" value="" class="size-5 accent-green" @checked(empty($filters['tag']))> {{ __('Toutes') }}</label>
                            @foreach ($tags as $tag)
                                <label class="{{ $radio }}"><input type="radio" name="tag" value="{{ $tag->slug }}" class="size-5 accent-green" @checked(($filters['tag'] ?? null) === $tag->slug)> {{ $tag->name }}</label>
                            @endforeach
                        </fieldset>
                    @endif

                    <fieldset class="grid gap-3">
                        <legend class="label-caps mb-1 text-ink-muted">{{ __('Prix (FCFA)') }}</legend>
                        <x-ui.field :label="__('Minimum')" name="min_price" type="number" inputmode="numeric" min="0" step="1"
                                    :value="$filters['min_price'] ?? ''" :placeholder="$priceRange['min']" />
                        <x-ui.field :label="__('Maximum')" name="max_price" type="number" inputmode="numeric" min="0" step="1"
                                    :value="$filters['max_price'] ?? ''" :placeholder="$priceRange['max']" />
                    </fieldset>

                    <label class="{{ $radio }}">
                        <input type="checkbox" name="sold_out" value="1" class="size-5 accent-green" @checked(! empty($filters['sold_out']))>
                        {{ __('Inclure les produits épuisés') }}
                    </label>

                    <div class="grid gap-1.5">
                        <label for="sort" class="text-sm font-semibold">{{ __('Trier par') }}</label>
                        <select id="sort" name="sort" class="min-h-12 w-full rounded-sm border border-field bg-white px-3 text-base">
                            @foreach ($sorts as $value => $label)
                                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-2">
                        <x-ui.button type="submit">{{ __('Appliquer') }}</x-ui.button>
                        @if ($filtered)
                            <x-ui.button variant="text" :href="route('shop.index')" class="justify-center">{{ __('Réinitialiser') }}</x-ui.button>
                        @endif
                    </div>
                </form>
            </details>

            <div>
                @if ($chips->isNotEmpty())
                    <ul class="mb-4 flex flex-wrap gap-2" aria-label="{{ __('Filtres actifs') }}">
                        @foreach ($chips as $chip)
                            <li>
                                <a href="{{ $chip['url'] }}" class="inline-flex min-h-11 items-center gap-2 border border-ink px-3 text-sm font-semibold hover:bg-ink hover:text-white">
                                    {{ $chip['label'] }}
                                    <x-ui.icon name="close" class="size-4" />
                                    <span class="sr-only">{{ __('retirer ce filtre') }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($products->isEmpty())
                    <x-ui.empty-state :title="__('Aucun produit ne correspond')" :text="__('Essayez de retirer un filtre ou de chercher autre chose.')">
                        <x-ui.button :href="route('shop.index')">{{ __('Voir toute la boutique') }}</x-ui.button>
                    </x-ui.empty-state>
                @else
                    <ul class="grid grid-cols-2 border-l border-t border-line xl:grid-cols-3">
                        @foreach ($products as $product)
                            <li class="contents"><x-storefront.product-card :product="$product" /></li>
                        @endforeach
                    </ul>

                    {{ $products->links('storefront.pagination') }}
                @endif
            </div>
        </div>
    </div>
</x-layouts.storefront>
