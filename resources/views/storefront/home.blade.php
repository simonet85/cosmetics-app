<x-layouts.storefront
    :title="__('Soins cosmétiques')"
    :description="__('Soins du visage, du corps et des cheveux : composition lisible, prix nets en FCFA, paiement mobile money.')"
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

    @php
        $payments = \App\Support\PaymentMethods::acceptedLabels();
        $proofs = array_filter([
            ['truck', \App\Support\Shipping::promise()],
            $payments ? ['smartphone', __('Paiement : :payments', ['payments' => implode(', ', array_map('mb_strtolower', $payments))])] : null,
            ['check', __('Commandez sans créer de compte')],
        ]);
    @endphp

    <div class="wrap pb-10 pt-6 sm:pb-14 sm:pt-10">
        <h1 class="sr-only">{{ __('Glowing Cosmetics, soins cosmétiques') }}</h1>

        @if ($slides->isEmpty())
            <x-ui.empty-state :title="__('La sélection arrive bientôt')" :text="__('Découvrez en attendant l\'ensemble de la boutique.')">
                <x-ui.button :href="route('shop.index')">{{ __('Voir la boutique') }}</x-ui.button>
            </x-ui.empty-state>
        @else
            <x-storefront.slider :label="__('À la une')" :count="$slides->count()">
                @foreach ($slides as [$kind, $item])
                    <div class="slider-slide" data-slide role="group" aria-roledescription="slide"
                         aria-label="{{ __(':current sur :total', ['current' => $loop->iteration, 'total' => $slides->count()]) }}">
                        @if ($kind === 'banner')
                            <x-storefront.banner :banner="$item" :eager="$loop->first" class="h-full" />
                        @else
                            <x-storefront.product-label :product="$item" :eager="$loop->first" class="h-full" />
                        @endif
                    </div>
                @endforeach
            </x-storefront.slider>
        @endif

        {{-- What a visitor needs to trust the shop, read from the settings: shown under the first screen, never invented --}}
        <ul class="mt-6 grid border border-ink bg-mint sm:grid-cols-3" aria-label="{{ __('Nos engagements') }}">
            @foreach ($proofs as [$icon, $text])
                <li class="flex min-h-14 items-center gap-3 px-4 py-3 text-sm font-semibold {{ ! $loop->first ? 'border-t border-ink sm:border-l sm:border-t-0' : '' }}">
                    <x-ui.icon :name="$icon" class="size-6 shrink-0 text-green-deep" />
                    <span>{{ $text }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    @if ($categories->isNotEmpty())
        @php
            $columns = [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4', 5 => 'lg:grid-cols-5', 6 => 'lg:grid-cols-6'][$categories->count()];
        @endphp
        <section class="wrap pb-12 sm:pb-16" aria-labelledby="univers">
            <div class="flex items-end justify-between gap-4">
                <h2 id="univers" class="text-3xl sm:text-4xl">{{ __('Les rayons') }}</h2>
                <x-ui.button variant="text" :href="route('shop.index')">{{ __('Toute la boutique') }}</x-ui.button>
            </div>
            <ul class="mt-6 grid grid-cols-2 border-l border-t border-ink sm:grid-cols-3 {{ $columns }}">
                @foreach ($categories as $category)
                    <li class="contents">
                        <a href="{{ route('shop.category', $category->slug) }}" class="group relative flex flex-col border-b border-r border-ink bg-white hover:bg-mint">
                            <span class="block bg-photo">
                                @if ($category->cover)
                                    <x-storefront.product-image :product="$category->cover" sizes="(min-width: 1024px) 16vw, 50vw" class="aspect-square object-contain" />
                                @else
                                    <span class="block aspect-square bg-green" aria-hidden="true"></span>
                                @endif
                            </span>
                            <span class="flex min-h-14 items-center justify-between gap-2 px-3 py-3 text-lg font-semibold uppercase leading-tight" style="font-stretch: 75%">
                                {{ $category->tr('name') }}
                                <x-ui.icon name="arrow-right" class="size-5 shrink-0" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @php
        $shelf = $featuredProducts->merge($bestSellers)->merge($hotProducts)
            ->unique('id')->reject(fn ($p) => $lead && $p->id === $lead->id)->take(8);
    @endphp
    @if ($shelf->isNotEmpty())
        <section class="wrap pb-12 sm:pb-16" aria-labelledby="selection">
            <div class="flex items-end justify-between gap-4">
                <h2 id="selection" class="text-3xl sm:text-4xl">{{ __('La sélection') }}</h2>
                <x-ui.button variant="text" :href="route('shop.index')">{{ __('Toute la boutique') }}</x-ui.button>
            </div>
            <ul class="mt-6 grid grid-cols-2 border-l border-t border-line lg:grid-cols-4">
                @foreach ($shelf as $product)
                    <li class="contents"><x-storefront.product-card :product="$product" /></li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="on-dark bg-green py-12 text-white sm:py-16" aria-labelledby="promesses">
        <div class="wrap">
            <h2 id="promesses" class="text-3xl sm:text-4xl">{{ __('Commander sans surprise') }}</h2>
            <dl class="mt-6 grid border-t border-white/40 md:grid-cols-3">
                @foreach ([
                    [__('Sans compte obligatoire'), __('Un compte vous permet de retrouver vos commandes et votre panier sur tous vos appareils. Commander reste possible sans compte.')],
                    [__('Annulation possible'), __('Tant que la commande est « En attente », vous pouvez l\'annuler depuis Mon compte.')],
                    [__('Suivi par e-mail'), __('Un e-mail vous prévient aussi quand l\'état change.')],
                ] as [$term, $detail])
                    <div class="border-b border-white/40 py-5 md:pr-6 {{ ! $loop->first ? 'md:border-l md:pl-6' : '' }}">
                        <dt class="text-xl font-semibold uppercase" style="font-stretch: 75%">{{ $term }}</dt>
                        <dd class="mt-2 max-w-prose text-white/90">{{ $detail }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    @if ($testimonials->isNotEmpty())
        <section class="bg-mint py-12 sm:py-16" aria-labelledby="avis">
            <div class="wrap">
                <h2 id="avis" class="text-3xl sm:text-4xl">{{ __('Ce que disent nos clientes') }}</h2>
                <ul class="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($testimonials as $testimonial)
                        <li>
                            <figure class="h-full border border-ink bg-white p-5">
                                <blockquote class="text-lg">{{ $testimonial->comment }}</blockquote>
                                <figcaption class="label-caps mt-4 text-ink-muted">
                                    {{ $testimonial->customer_name }}
                                    @if ($testimonial->rating)<span class="sr-only"> {{ __(': :rating sur 5', ['rating' => $testimonial->rating]) }}</span>@endif
                                </figcaption>
                            </figure>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <div class="wrap py-12 sm:py-16">
        @isset($banners['bottom'])
            <x-storefront.banner :banner="$banners['bottom']" />
        @else
            <div class="flex flex-col items-start gap-4 border border-ink p-6 sm:flex-row sm:items-center sm:justify-between sm:p-10">
                <p class="text-3xl uppercase sm:text-4xl" style="font-stretch: 75%">{{ __('Toute la boutique') }}</p>
                <x-ui.button :href="route('shop.index')">{{ __('Voir la boutique') }}</x-ui.button>
            </div>
        @endisset
    </div>
</x-layouts.storefront>
