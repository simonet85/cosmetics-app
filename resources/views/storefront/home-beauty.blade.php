@php
    // ---- Hero: a slider. Admin banners first; without any, the brand slide, then featured products to keep it a sequence.
    $slides = collect();
    foreach (['top', 'middle'] as $slot) {
        if (isset($banners[$slot])) { $slides->push(['banner', $banners[$slot]]); }
    }
    if ($slides->isEmpty()) { $slides->push(['brand', null]); }

    $pool = $featuredProducts->merge($bestSellers)->merge($hotProducts)->unique('id')->values();
    while ($slides->count() < 3 && $pool->isNotEmpty()) { $slides->push(['product', $pool->shift()]); }
    $heroIds = $slides->filter(fn ($slide) => $slide[0] === 'product')->map(fn ($slide) => $slide[1]->id);

    // ---- Two shelves of three products, never the same product twice on the page
    $best = $bestSellers->reject(fn ($p) => $heroIds->contains($p->id))->take(3)->values();
    $featured = $featuredProducts->reject(fn ($p) => $heroIds->contains($p->id) || $best->contains('id', $p->id))->take(3)->values();

    $rayons = $categories->take(3);
    $inWishlist = fn ($product) => in_array($product->id, $wishlistIds, true);
    $assets = 'images/templates/beauty';
@endphp

<x-layouts.storefront
    :title="__('Soins cosmétiques')"
    :description="__('Soins du visage, du corps et des cheveux : composition lisible, prix nets en FCFA, paiement mobile money.')"
>
    <x-slot:head>
        {!! \App\Support\StructuredData::script(\App\Support\StructuredData::organization(), \App\Support\StructuredData::website()) !!}
    </x-slot:head>
    <div class="tpl-beauty" data-template="beauty">
        <h1 class="sr-only">{{ __('Glowing Cosmetics, soins cosmétiques') }}</h1>

        {{-- 1. Hero slider --}}
        <div class="wrap pt-6 sm:pt-10">
            <x-storefront.slider :label="__('À la une')" :count="$slides->count()">
                @foreach ($slides as [$kind, $item])
                    <div class="slider-slide" data-slide role="group" aria-roledescription="slide"
                         aria-label="{{ __(':current sur :total', ['current' => $loop->iteration, 'total' => $slides->count()]) }}">
                        @if ($kind === 'product')
                            <x-storefront.product-label :product="$item" :eager="$loop->first" class="h-full" />
                        @else
                            @php
                                $isBrand = $kind === 'brand';
                                $image = $isBrand ? asset($assets.'/hero.jpg') : asset($item->image_path);
                                $accent = $isBrand ? __('Soins de la peau') : $item->tr('subtitle');
                                $heading = $isBrand ? __('Révélez l\'éclat de votre peau') : $item->tr('title');
                                $text = $isBrand ? __('Des soins du visage, du corps et des cheveux : prix nets en FCFA et paiement mobile money.') : $item->tr('description');
                                $link = $isBrand ? route('shop.index') : $item->link_url;
                                $label = $isBrand ? __('Voir la boutique') : (filled($item->tr('button_text')) ? $item->tr('button_text') : __('Découvrir'));
                            @endphp
                            <section class="relative bg-mint md:min-h-[26rem] lg:min-h-[30rem]" @if (! $isBrand) aria-labelledby="hero-banner-{{ $item->id }}" @else aria-labelledby="hero-brand" @endif>
                                <img src="{{ $image }}" alt="" width="1600" height="893" loading="{{ $loop->first ? 'eager' : 'lazy' }}" decoding="async" @if ($loop->first) fetchpriority="high" @endif
                                     class="aspect-[4/3] w-full object-cover md:absolute md:inset-0 md:aspect-auto md:h-full">
                                <div class="relative flex p-4 md:min-h-[26rem] md:items-center md:px-12 lg:min-h-[30rem]">
                                    <div class="w-full bg-white p-6 md:max-w-md md:p-8">
                                        @if (filled($accent))<p class="script text-2xl text-green-deep">{{ $accent }}</p>@endif
                                        <h2 id="{{ $isBrand ? 'hero-brand' : 'hero-banner-'.$item->id }}" class="mt-1 whitespace-pre-line text-3xl sm:text-4xl">{{ $heading }}</h2>
                                        @if (filled($text))<p class="mt-3 text-ink-muted">{{ $text }}</p>@endif
                                        @if (filled($link))<div class="mt-5"><x-ui.button :href="$link">{{ $label }}</x-ui.button></div>@endif
                                    </div>
                                </div>
                            </section>
                        @endif
                    </div>
                @endforeach
            </x-storefront.slider>
        </div>

        {{-- 2. Three rayons with a photo --}}
        @if ($rayons->isNotEmpty())
            <section class="wrap py-12 sm:py-16" aria-labelledby="rayons">
                <h2 id="rayons" class="sr-only">{{ __('Les rayons') }}</h2>
                <ul class="grid gap-6 sm:grid-cols-{{ min($rayons->count(), 3) }}">
                    @foreach ($rayons as $rayon)
                        <li class="relative text-center">
                            <div class="bg-photo">
                                <x-storefront.product-image :product="$rayon->cover" sizes="(min-width: 640px) 30vw, 100vw" class="aspect-[4/3] object-contain" />
                            </div>
                            <h3 class="mt-4 text-xl">
                                <a href="{{ route('shop.category', $rayon->slug) }}" class="after:absolute after:inset-0 hover:underline">{{ $rayon->tr('name') }}</a>
                            </h3>
                            <p class="mt-1 text-sm font-semibold underline underline-offset-4">{{ __('Voir le rayon') }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- 3. Best sellers and featured products: the title on the left, three products on the right --}}
        @foreach ([
            ['best-sellers', $best, __('Meilleures ventes'), __('Les soins que nos clientes rachètent'), __('Les produits les plus commandés de la boutique.')],
            ['featured', $featured, __('En vedette'), __('Notre sélection du moment'), __('Des produits choisis un par un pour leur composition et leur efficacité.')],
        ] as [$id, $products, $accent, $heading, $text])
            @if ($products->isNotEmpty())
                <section class="wrap pb-12 sm:pb-16" aria-labelledby="{{ $id }}">
                    <div class="grid items-center gap-8 lg:grid-cols-[18rem_minmax(0,1fr)] lg:gap-12">
                        <div class="text-center lg:text-left">
                            <p class="script text-3xl text-green-deep">{{ $accent }}</p>
                            <h2 id="{{ $id }}" class="mt-1 text-3xl sm:text-4xl">{{ $heading }}</h2>
                            <p class="mt-3 text-ink-muted">{{ $text }}</p>
                            <p class="mt-4"><a href="{{ route('shop.index') }}" class="inline-flex min-h-11 items-center text-sm font-semibold underline underline-offset-4 hover:text-green-deep">{{ __('Voir la boutique') }}</a></p>
                        </div>
                        <ul class="grid grid-cols-2 gap-x-4 gap-y-10 sm:grid-cols-3">
                            @foreach ($products as $product)
                                <li><x-storefront.product-tile :product="$product" :in-wishlist="$inWishlist($product)" /></li>
                            @endforeach
                        </ul>
                    </div>
                </section>
            @endif
        @endforeach

        {{-- 4. Before / after --}}
        <section class="bg-mint" aria-labelledby="compare-title">
            <div class="wrap grid items-center gap-8 py-12 sm:py-16 md:grid-cols-2 md:gap-12">
                <div class="compare aspect-[4/5] bg-ink" data-compare>
                    <img src="{{ asset($assets.'/before.jpg') }}" alt="{{ __('Visage avant le soin') }}" width="800" height="1000" loading="lazy" decoding="async">
                    <div class="compare-after">
                        <img src="{{ asset($assets.'/after.jpg') }}" alt="{{ __('Visage après le soin') }}" width="800" height="1000" loading="lazy" decoding="async">
                    </div>
                    <div class="compare-handle" aria-hidden="true"></div>
                    <input type="range" min="0" max="100" value="50" aria-label="{{ __('Comparer avant et après') }}">
                </div>
                <div>
                    <p class="script text-3xl text-green-deep">{{ __('Avant et après') }}</p>
                    <h2 id="compare-title" class="mt-1 text-3xl sm:text-4xl">{{ __('Faites glisser pour comparer') }}</h2>
                    <p class="mt-3 max-w-prose text-ink-muted">{{ __('Les résultats varient d\'une personne à l\'autre. Découvrez les rayons de la boutique pour trouver le soin qui vous convient.') }}</p>
                    @if ($categories->isNotEmpty())
                        <ul class="mt-6 grid gap-1 sm:grid-cols-2">
                            @foreach ($categories->take(4) as $rayon)
                                <li>
                                    <a href="{{ route('shop.category', $rayon->slug) }}" class="flex min-h-11 items-center gap-2 font-semibold hover:underline">
                                        <x-ui.icon name="check" class="size-5 shrink-0 text-green-deep" /> {{ $rayon->tr('name') }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="mt-6 flex flex-wrap gap-3">
                        <x-ui.button :href="route('shop.index')">{{ __('Boutique') }}</x-ui.button>
                        <x-ui.button variant="secondary" :href="route('pages.about')">{{ __('En savoir plus') }}</x-ui.button>
                    </div>
                </div>
            </div>
        </section>

        {{-- 5. Limited-time offer, only while its end date (a setting) is in the future --}}
        @if ($offer)
            @php
                $left = max(0, now()->diffInSeconds($offer['ends_at'], false));
                $units = [
                    'days' => [__('Jours'), intdiv((int) $left, 86400)],
                    'hours' => [__('Heures'), intdiv((int) $left % 86400, 3600)],
                    'minutes' => [__('Min'), intdiv((int) $left % 3600, 60)],
                    'seconds' => [__('Sec'), (int) $left % 60],
                ];
            @endphp
            <section class="wrap py-12 sm:py-16" aria-labelledby="offer-title">
                <div class="grid items-center gap-8 md:grid-cols-2 md:gap-12">
                    <div>
                        <p class="script text-3xl text-green-deep">{{ __('Offre à durée limitée') }}</p>
                        <h2 id="offer-title" class="mt-1 text-3xl sm:text-4xl">{{ filled($offer['title']) ? $offer['title'] : __('Profitez de nos soins avant la fin de l\'offre') }}</h2>
                        @if (filled($offer['text']))<p class="mt-3 max-w-prose text-ink-muted">{{ $offer['text'] }}</p>@endif

                        <p class="sr-only">{{ __('L\'offre se termine le :date.', ['date' => $offer['ends_at']->translatedFormat('j F Y à H:i')]) }}</p>
                        <dl class="mt-6 flex gap-4" data-countdown="{{ $offer['ends_at']->toIso8601String() }}" aria-hidden="true">
                            @foreach ($units as $unit => [$label, $value])
                                <div class="text-center">
                                    <dd class="tnum text-4xl font-semibold" data-unit="{{ $unit }}">{{ str_pad((string) $value, 2, '0', STR_PAD_LEFT) }}</dd>
                                    <dt class="text-xs uppercase tracking-wide text-ink-muted">{{ $label }}</dt>
                                </div>
                            @endforeach
                        </dl>

                        <div class="mt-6"><x-ui.button :href="route('shop.index')">{{ __('Voir la boutique') }}</x-ui.button></div>
                    </div>
                    <img src="{{ asset($assets.'/offer.jpg') }}" alt="" width="1000" height="1210" loading="lazy" decoding="async" class="mx-auto w-full max-w-md">
                </div>
            </section>
        @endif
    </div>
</x-layouts.storefront>
