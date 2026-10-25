@php
    $final = (float) $product->final_price;
    $list = (float) $product->price;
    $hasDiscount = $final < $list;
    $variants = $product->variants;
    $firstVariant = $variants->firstWhere('stock', '>', 0);
    $images = $product->images;
    $mainImage = $product->primary_image?->path ?? 'images/products/product-placeholder.jpg';
    $category = $product->categories->first();
    $composition = collect(preg_split('/[\r\n,;]+/', (string) $product->tr('ingredients')))->map(fn ($l) => trim($l))->filter();
    $payments = \App\Support\PaymentMethods::acceptedLabels();
    $lowStock = $product->stock > 0 && $product->stock <= 5;
    $maxQty = max(1, $firstVariant ? $firstVariant->stock : $product->stock);

    // Short description first, then the start of the full one, then a generic sentence
    $metaDescription = $product->tr('short_description')
        ?: (trim(strip_tags((string) $product->tr('full_description'))) !== ''
            ? \Illuminate\Support\Str::limit(trim(strip_tags((string) $product->tr('full_description'))), 155)
            : __('Découvrez :name : prix net en FCFA, composition et livraison.', ['name' => $product->tr('name')]));

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->tr('name'),
        'sku' => $product->sku,
        'description' => $product->tr('short_description') ?: \Illuminate\Support\Str::limit(strip_tags((string) $product->tr('full_description')), 200),
        'image' => $images->isNotEmpty() ? $images->map(fn ($i) => asset($i->path))->values()->all() : [asset($mainImage)],
        'offers' => [
            '@type' => 'Offer',
            'url' => route('products.show', $product->slug),
            'priceCurrency' => 'XOF',
            'price' => (string) (int) round($final),
            'availability' => $product->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        ],
    ];
    if ($reviewCount > 0) {
        $jsonLd['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => (string) $reviewAverage, 'reviewCount' => $reviewCount];
    }
    // First name and last initial only: reviews are public
    $nameOf = fn ($user) => $user && trim((string) $user->first_name) !== ''
        ? trim($user->first_name).(trim((string) $user->last_name) !== '' ? ' '.mb_substr(trim($user->last_name), 0, 1).'.' : '')
        : 'Cliente';
@endphp

<x-layouts.storefront
    :title="$product->tr('name')"
    :description="$metaDescription"
    og-type="product"
    :image="asset($mainImage)"
    :canonical="route('products.show', $product->slug)"
    :robots="$isPreview ? 'noindex,nofollow' : null"
>
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
        {!! \App\Support\StructuredData::script(\App\Support\StructuredData::breadcrumbs(array_filter([
            [__('Accueil'), route('home')],
            [__('Boutique'), route('shop.index')],
            $category ? [$category->tr('name'), route('shop.category', $category->slug)] : null,
            [$product->tr('name'), route('products.show', $product->slug)],
        ]))) !!}
        <meta property="product:price:amount" content="{{ (int) round($final) }}">
        <meta property="product:price:currency" content="XOF">
        <meta property="product:availability" content="{{ $product->stock > 0 ? 'in stock' : 'out of stock' }}">
        <meta property="og:image:alt" content="{{ $product->tr('name') }}">
    </x-slot:head>

    @if ($isPreview)
        <div class="bg-ticket px-4 py-2 text-center text-sm font-semibold">
            {{ __('Aperçu administrateur : ce produit est :state et n\'est pas visible par les clients.', ['state' => $product->status === 'archived' ? __('archivé') : __('inactif')]) }}
        </div>
    @endif

    <div class="wrap py-6 sm:py-10">
        <nav aria-label="{{ __('Fil d\'Ariane') }}" class="flex flex-wrap items-center text-sm text-ink-muted">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center underline">{{ __('Accueil') }}</a>
            <span aria-hidden="true" class="mx-2">/</span>
            <a href="{{ route('shop.index') }}" class="inline-flex min-h-11 items-center underline">{{ __('Boutique') }}</a>
            @if ($category)
                <span aria-hidden="true" class="mx-2">/</span>
                <a href="{{ route('shop.category', $category->slug) }}" class="inline-flex min-h-11 items-center underline">{{ $category->tr('name') }}</a>
            @endif
        </nav>

        <div class="mt-4 grid gap-6 lg:grid-cols-[minmax(0,6fr)_minmax(0,5fr)] lg:gap-12">
            {{-- Photos --}}
            <div>
                <div class="border border-ink bg-photo">
                    <img id="product-main-image" src="{{ asset($mainImage) }}" alt="{{ $product->tr('name') }}" width="330" height="440"
                         fetchpriority="high" class="mx-auto aspect-[4/3] h-auto w-full object-contain lg:aspect-auto lg:h-[34rem]">
                </div>
                @if ($images->count() > 1)
                    <ul class="mt-2 grid grid-cols-4 gap-2" aria-label="{{ __('Photos du produit') }}">
                        @foreach ($images as $image)
                            <li>
                                <button type="button" data-gallery-thumb="{{ asset($image->path) }}"
                                        aria-label="{{ __('Voir la photo :current sur :total', ['current' => $loop->iteration, 'total' => $images->count()]) }}"
                                        @if ($image->path === ($product->primary_image?->path)) aria-current="true" @endif
                                        class="block min-h-11 w-full cursor-pointer border border-line bg-photo aria-[current=true]:border-ink aria-[current=true]:border-2">
                                    <img src="{{ asset($image->path) }}" alt="" width="330" height="440" loading="lazy" class="aspect-square h-auto w-full object-contain">
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Label --}}
            <div>
                <div class="flex flex-wrap gap-2">
                    @if ($product->is_featured)<x-ui.badge tone="ticket">{{ __('En vedette') }}</x-ui.badge>@endif
                    @if ($product->is_best_seller)<x-ui.badge tone="mint">{{ __('Meilleure vente') }}</x-ui.badge>@endif
                    @if ($hasDiscount)<x-ui.badge tone="ink">-{{ (int) round((1 - $final / $list) * 100) }} %</x-ui.badge>@endif
                </div>

                <h1 class="mt-3 text-4xl uppercase sm:text-5xl">{{ $product->tr('name') }}</h1>

                @if ($reviewCount > 0)
                    <a href="#avis" class="mt-2 inline-flex min-h-11 items-center gap-2 text-sm underline">
                        <span class="flex" aria-hidden="true">
                            @for ($i = 1; $i <= 5; $i++)<x-ui.icon name="star" @class(['size-4', 'text-amber' => $i <= round($reviewAverage), 'text-line' => $i > round($reviewAverage)]) />@endfor
                        </span>
                        <span class="tnum">{{ __(':reviewaverage sur 5', ['reviewaverage' => number_format($reviewAverage, 1, ',', '')]) }}</span>
                        <span>({{ $reviewCount }} {{ $reviewCount > 1 ? 'avis' : 'avis' }})</span>
                    </a>
                @endif

                @if ($product->tr('short_description'))
                    <p class="mt-3 max-w-prose text-lg text-ink-muted">{{ $product->tr('short_description') }}</p>
                @endif

                <form method="POST" action="{{ route('cart.add') }}" data-add-to-cart data-product-form class="mt-6 grid gap-5">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    <div class="ticket-wrap" data-reveal>
                        <div class="ticket inline-block bg-ticket px-4 py-3">
                            <p class="label-caps">{{ __('Prix net') }}</p>
                            <p class="tnum flex flex-wrap items-baseline gap-x-3" style="font-stretch: 75%">
                                <span id="product-price" class="text-5xl font-bold leading-none sm:text-6xl">{{ format_fcfa($firstVariant ? $final + $firstVariant->price_modifier : $final) }}</span>
                                @if ($hasDiscount)
                                    <span class="sr-only">{{ __('au lieu de') }}</span>
                                    <s id="product-list-price" class="text-base font-normal text-ink-muted">{{ format_fcfa($firstVariant ? $list + $firstVariant->price_modifier : $list) }}</s>
                                @endif
                            </p>
                        </div>
                    </div>

                    @if ($variants->isNotEmpty())
                        <fieldset class="grid gap-2">
                            <legend class="label-caps mb-1 text-ink-muted">{{ __('Contenance / option') }}</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($variants as $variant)
                                    @php
                                        $label = collect([$variant->size, $variant->color_name ?? $variant->color])->filter()->implode(' / ') ?: $variant->sku;
                                        $soldOut = $variant->stock <= 0;
                                    @endphp
                                    <label class="relative">
                                        <input type="radio" name="variant_id" value="{{ $variant->id }}" class="peer sr-only"
                                               data-price="{{ $final + $variant->price_modifier }}"
                                               data-list-price="{{ $list + $variant->price_modifier }}"
                                               data-stock="{{ $variant->stock }}"
                                               @checked($firstVariant && $variant->id === $firstVariant->id) @disabled($soldOut) required>
                                        <span class="inline-flex min-h-12 cursor-pointer items-center gap-1 border border-field px-4 font-semibold peer-checked:border-ink peer-checked:bg-ink peer-checked:text-white peer-focus-visible:outline peer-focus-visible:outline-[3px] peer-focus-visible:outline-offset-2 peer-focus-visible:outline-green-deep peer-disabled:cursor-not-allowed peer-disabled:text-ink-muted peer-disabled:line-through">
                                            {{ $label }}
                                            @if ($variant->price_modifier != 0)
                                                <span class="text-sm font-normal">({{ $variant->price_modifier > 0 ? '+' : '-' }}{{ format_fcfa(abs($variant->price_modifier)) }})</span>
                                            @endif
                                            @if ($soldOut)<span class="sr-only"> {{ __('(épuisé)') }}</span>@endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    <p id="product-stock" class="flex items-center gap-2 text-sm {{ $product->stock <= 0 ? 'text-danger' : ($lowStock ? 'text-amber' : 'text-ink-muted') }}">
                        @if ($product->stock <= 0)
                            <x-ui.icon name="alert" class="size-4" /> {{ __('Épuisé pour le moment') }}
                        @elseif ($lowStock)
                            <x-ui.icon name="alert" class="size-4" /> {{ __('Plus que :stock en stock', ['stock' => $product->stock]) }}
                        @else
                            <x-ui.icon name="check" class="size-4" /> {{ __('En stock') }}
                        @endif
                    </p>

                    @if ($product->stock > 0)
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center border border-ink" role="group" aria-label="{{ __('Quantité') }}">
                                <button type="button" data-qty-step="-1" aria-label="{{ __('Retirer une unité') }}" class="inline-flex size-12 cursor-pointer items-center justify-center hover:bg-mint">
                                    <x-ui.icon name="minus" class="size-5" />
                                </button>
                                <input type="number" name="quantity" value="1" min="1" max="{{ $maxQty }}" inputmode="numeric" data-qty
                                       aria-label="{{ __('Quantité') }}" class="tnum h-12 w-14 border-x border-ink text-center text-lg [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                                <button type="button" data-qty-step="1" aria-label="{{ __('Ajouter une unité') }}" class="inline-flex size-12 cursor-pointer items-center justify-center hover:bg-mint">
                                    <x-ui.icon name="plus" class="size-5" />
                                </button>
                            </div>
                            <button type="button" data-wishlist-toggle="{{ $product->id }}" aria-pressed="{{ $inWishlist ? 'true' : 'false' }}"
                                    class="group inline-flex size-12 cursor-pointer items-center justify-center border border-ink hover:bg-mint">
                                <x-ui.icon name="heart" class="size-6 group-aria-pressed:fill-current" />
                                <span class="sr-only">{{ __('Mettre dans mes favoris') }}</span>
                            </button>
                        </div>

                        <x-ui.button type="submit" class="w-full sm:max-w-md">
                            <x-ui.icon name="bag" class="size-5" />
                            <span data-label>{{ __('Ajouter au panier') }}</span>
                        </x-ui.button>
                    @else
                        <x-ui.button type="button" variant="secondary" disabled class="w-full sm:max-w-md">{{ __('Épuisé') }}</x-ui.button>
                    @endif
                </form>

                <ul class="mt-6 divide-y divide-line border-y border-line text-sm">
                    <li class="flex items-center gap-3 py-3">
                        <x-ui.icon name="truck" class="size-5 shrink-0 text-green-deep" />
                        <span>{{ \App\Support\Shipping::promise() }}</span>
                    </li>
                    <li class="flex items-center gap-3 py-3">
                        <x-ui.icon name="smartphone" class="size-5 shrink-0 text-green-deep" />
                        <span>{{ __('Paiement : :payments', ['payments' => implode(', ', $payments)]) }}</span>
                    </li>
                </ul>

                <dl class="mt-4 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">
                    <dt class="text-ink-muted">{{ __('Référence') }}</dt><dd>{{ $product->sku }}</dd>
                    @if ($product->tags->isNotEmpty())
                        <dt class="text-ink-muted">{{ __('Étiquettes') }}</dt>
                        <dd class="flex flex-wrap gap-x-3">
                            @foreach ($product->tags as $tag)
                                <a href="{{ route('shop.index', ['tag' => $tag->slug]) }}" class="inline-flex min-h-11 items-center underline">{{ $tag->tr('name') }}</a>
                            @endforeach
                        </dd>
                    @endif
                </dl>

                <div class="mt-4 flex flex-wrap items-center gap-x-2 text-sm">
                    <span class="text-ink-muted">{{ __('Partager :') }}</span>
                    @php
                        $shareUrl = rawurlencode(route('products.show', $product->slug));
                        $shareText = rawurlencode($product->tr('name'));
                    @endphp
                    <a href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center underline">{{ __('WhatsApp') }}<span class="sr-only"> {{ __('(nouvel onglet)') }}</span></a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center px-2 underline">{{ __('Facebook') }}<span class="sr-only"> {{ __('(nouvel onglet)') }}</span></a>
                </div>
            </div>
        </div>

        {{-- Details --}}
        <div class="mt-12 grid gap-px border border-ink bg-ink lg:grid-cols-2">
            @if ($composition->isNotEmpty())
                <section class="bg-white p-5 sm:p-8" aria-labelledby="composition">
                    <h2 id="composition" class="text-3xl">{{ __('Composition') }}</h2>
                    <ul class="mt-4 divide-y divide-line border-t border-line">
                        @foreach ($composition as $line)<li class="py-2">{{ $line }}</li>@endforeach
                    </ul>
                </section>
            @endif
            @if ($product->tr('how_to_use'))
                <section class="bg-white p-5 sm:p-8" aria-labelledby="usage">
                    <h2 id="usage" class="text-3xl">{{ __('Conseils d\'utilisation') }}</h2>
                    <p class="mt-4 whitespace-pre-line">{{ $product->tr('how_to_use') }}</p>
                </section>
            @endif
            @if ($product->tr('full_description'))
                <section class="bg-white p-5 sm:p-8 lg:col-span-2" aria-labelledby="description">
                    <h2 id="description" class="text-3xl">{{ __('Description') }}</h2>
                    <p class="mt-4 max-w-prose whitespace-pre-line">{{ $product->tr('full_description') }}</p>
                </section>
            @endif
        </div>

        {{-- Reviews --}}
        <section class="mt-12" id="avis" aria-labelledby="avis-title">
            <h2 id="avis-title" class="text-3xl sm:text-4xl">{{ __('Avis clients') }}</h2>

            @if (session('success'))
                <p role="status" class="mt-4 border border-green bg-mint px-4 py-3 font-semibold">{{ session('success') }}</p>
            @endif

            @if ($reviews->isEmpty())
                <p class="mt-4 text-ink-muted">{{ __('Aucun avis pour le moment.') }}</p>
            @else
                <ul class="mt-4 divide-y divide-line border-y border-ink">
                    @foreach ($reviews as $review)
                        <li class="py-4">
                            <p class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="font-semibold">{{ $nameOf($review->user) }}</span>
                                <span class="tnum text-sm text-ink-muted">{{ __(':rating sur 5', ['rating' => $review->rating]) }}</span>
                                <time class="text-sm text-ink-muted" datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->translatedFormat('j F Y') }}</time>
                            </p>
                            @if ($review->title)<p class="mt-1 font-semibold">{{ $review->title }}</p>@endif
                            <p class="mt-1 max-w-prose">{{ $review->comment }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-8 max-w-xl">
                @auth
                    <h3 class="text-2xl">{{ __('Écrire un avis') }}</h3>
                    <form method="POST" action="{{ route('products.reviews.store', $product->slug) }}" class="mt-4 grid gap-5">
                        @csrf
                        <fieldset class="grid gap-1">
                            <legend class="text-sm font-semibold">{{ __('Votre note') }} <span class="text-danger" aria-hidden="true">*</span></legend>
                            <div class="flex flex-wrap gap-2">
                                @for ($i = 1; $i <= 5; $i++)
                                    <label class="relative">
                                        <input type="radio" name="rating" value="{{ $i }}" class="peer sr-only" required @checked((int) old('rating') === $i)>
                                        <span class="tnum inline-flex size-12 cursor-pointer items-center justify-center border border-field text-lg font-semibold peer-checked:border-ink peer-checked:bg-ink peer-checked:text-white peer-focus-visible:outline peer-focus-visible:outline-[3px] peer-focus-visible:outline-offset-2 peer-focus-visible:outline-green-deep">{{ $i }}<span class="sr-only"> {{ __('sur 5') }}</span></span>
                                    </label>
                                @endfor
                            </div>
                            @error('rating')<p class="text-sm font-medium text-danger">{{ $message }}</p>@enderror
                        </fieldset>
                        <x-ui.field :label="__('Titre')" name="title" :value="old('title')" :error="$errors->first('title')" required />
                        <div class="grid gap-1.5">
                            <label for="field-comment" class="text-sm font-semibold">{{ __('Votre avis') }} <span class="text-danger" aria-hidden="true">*</span></label>
                            <textarea id="field-comment" name="comment" rows="5" required @if ($errors->has('comment')) aria-invalid="true" @endif
                                      class="w-full rounded-sm border border-field bg-white p-3 text-base">{{ old('comment') }}</textarea>
                            @error('comment')<p class="text-sm font-medium text-danger">{{ $message }}</p>@enderror
                        </div>
                        <p class="text-sm text-ink-muted">{{ __('Votre avis est publié après vérification.') }}</p>
                        <x-ui.button type="submit" variant="secondary" class="w-full sm:w-auto">{{ __('Envoyer mon avis') }}</x-ui.button>
                    </form>
                @else
                    <p><a href="{{ route('login') }}" class="inline-flex min-h-11 items-center font-semibold underline">{{ __('Connectez-vous pour donner votre avis') }}</a></p>
                @endauth
            </div>
        </section>

        @if ($relatedProducts->isNotEmpty())
            <section class="mt-12" aria-labelledby="related">
                <h2 id="related" class="text-3xl sm:text-4xl">{{ __('Dans le même rayon') }}</h2>
                <ul class="mt-6 grid grid-cols-2 border-l border-t border-line lg:grid-cols-4">
                    @foreach ($relatedProducts as $related)
                        <li class="contents"><x-storefront.product-card :product="$related" /></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.storefront>
