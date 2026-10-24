<x-layouts.storefront
    title="Soins cosmétiques"
    description="Soins du visage, du corps et des cheveux : composition lisible, prix nets en FCFA, paiement mobile money."
>
    @php
        $lead = $featuredProducts->first();

        // The first screen is a sequence 1 -> 2 -> 3: the top banner, the featured product, the middle banner.
        // A part that does not exist is simply not a slide; one slide alone is shown without any control.
        $slides = collect([
            isset($banners['top']) ? ['banner', $banners['top']] : null,
            $lead ? ['product', $lead] : null,
            isset($banners['middle']) ? ['banner', $banners['middle']] : null,
        ])->filter()->values();
    @endphp

    <div class="wrap py-8 sm:py-12">
        <h1 class="sr-only">Glowing Cosmetics, soins cosmétiques</h1>

        @if ($slides->isEmpty())
            <x-ui.empty-state title="La sélection arrive bientôt" text="Découvrez en attendant l'ensemble de la boutique.">
                <x-ui.button :href="route('shop.index')">Voir la boutique</x-ui.button>
            </x-ui.empty-state>
        @else
            <x-storefront.slider label="À la une" :count="$slides->count()">
                @foreach ($slides as [$kind, $item])
                    <div class="slider-slide" data-slide role="group" aria-roledescription="slide"
                         aria-label="{{ $loop->iteration }} sur {{ $slides->count() }}">
                        @if ($kind === 'banner')
                            <x-storefront.banner :banner="$item" :eager="$loop->first" class="h-full" />
                        @else
                            <x-storefront.product-label :product="$item" :eager="$loop->first" class="h-full" />
                        @endif
                    </div>
                @endforeach
            </x-storefront.slider>
        @endif
    </div>

    @if ($categories->isNotEmpty())
        <section class="on-dark bg-green py-12 text-white sm:py-16" aria-labelledby="univers">
            <div class="wrap">
                <h2 id="univers" class="text-3xl sm:text-4xl">Les rayons</h2>
                <ul class="mt-6 grid border-t border-white/30 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($categories as $category)
                        <li class="border-b border-white/30">
                            <a href="{{ route('shop.category', $category->slug) }}"
                               class="flex min-h-16 items-center justify-between gap-4 px-4 py-4 text-xl font-semibold uppercase hover:bg-green-deep" style="font-stretch: 75%">
                                {{ $category->name }}
                                <x-ui.icon name="arrow-right" class="size-5 shrink-0" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @php
        $shelf = $featuredProducts->merge($bestSellers)->merge($hotProducts)
            ->unique('id')->reject(fn ($p) => $lead && $p->id === $lead->id)->take(8);
    @endphp
    @if ($shelf->isNotEmpty())
        <section class="wrap py-12 sm:py-16" aria-labelledby="selection">
            <div class="flex items-end justify-between gap-4">
                <h2 id="selection" class="text-3xl sm:text-4xl">La sélection</h2>
                <x-ui.button variant="text" :href="route('shop.index')">Toute la boutique</x-ui.button>
            </div>
            <ul class="mt-6 divide-y divide-line border-y border-ink">
                @foreach ($shelf as $product)
                    <li class="grid grid-cols-[5rem_1fr] items-center gap-x-4 gap-y-3 py-4 sm:grid-cols-[6rem_1fr_auto_auto] sm:gap-x-6" data-reveal style="--reveal-index: {{ $loop->index }}">
                        <a href="{{ route('products.show', $product->slug) }}" tabindex="-1" aria-hidden="true" class="bg-photo">
                            <x-storefront.product-image :product="$product" sizes="96px" class="aspect-[3/4]" />
                        </a>
                        <div>
                            <h3 class="text-xl uppercase">
                                <a href="{{ route('products.show', $product->slug) }}" class="inline-block py-1 hover:underline">{{ $product->name }}</a>
                            </h3>
                            @if ($product->categories->isNotEmpty())
                                <p class="text-sm text-ink-muted">{{ $product->categories->first()->name }}</p>
                            @endif
                        </div>
                        <x-ui.price :amount="$product->final_price" :original="$product->discount_price > 0 ? $product->price : null" size="lg" class="col-start-2 sm:col-start-auto" />
                        <x-storefront.add-to-cart :product="$product" class="col-span-2 sm:col-span-1" />
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($testimonials->isNotEmpty())
        <section class="bg-mint py-12 sm:py-16" aria-labelledby="avis">
            <div class="wrap">
                <h2 id="avis" class="text-3xl sm:text-4xl">Ce que disent nos clientes</h2>
                <ul class="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($testimonials as $testimonial)
                        <li>
                            <figure class="h-full border border-ink bg-white p-5">
                                <blockquote class="text-lg">{{ $testimonial->comment }}</blockquote>
                                <figcaption class="label-caps mt-4 text-ink-muted">
                                    {{ $testimonial->customer_name }}
                                    @if ($testimonial->rating)<span class="sr-only"> : {{ $testimonial->rating }} sur 5</span>@endif
                                </figcaption>
                            </figure>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @isset($banners['bottom'])
        <div class="wrap pb-12 sm:pb-16"><x-storefront.banner :banner="$banners['bottom']" /></div>
    @endisset
</x-layouts.storefront>
