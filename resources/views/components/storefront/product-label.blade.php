@props(['product'])

{{--
    The lead product as a pharmacy shelf label: photo, name in condensed capitals, composition, net price, stock, action.
    Composition lines are the first three entries of the real `ingredients` field; nothing is shown when it is empty.
--}}
@php
    $composition = collect(preg_split('/[\r\n,;]+/', (string) $product->ingredients))
        ->map(fn ($line) => trim($line))->filter()->take(3);
    $original = $product->discount_price > 0 ? $product->price : null;
    $lowStock = $product->stock > 0 && $product->stock <= 5;
@endphp

<article class="grid gap-0 border border-ink bg-white lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]" aria-labelledby="lead-name">
    <a href="{{ route('products.show', $product->slug) }}" class="block bg-mint" tabindex="-1" aria-hidden="true">
        <x-storefront.product-image :product="$product" :eager="true" class="aspect-[4/3] object-contain lg:aspect-auto lg:h-[26rem]" />
    </a>

    <div class="flex flex-col justify-between gap-5 p-5 sm:p-8">
        <div>
            <h2 id="lead-name" class="text-4xl uppercase sm:text-5xl">
                <a href="{{ route('products.show', $product->slug) }}" class="hover:underline">{{ $product->name }}</a>
            </h2>
            @if ($product->short_description)
                <p class="mt-3 max-w-prose text-ink-muted">{{ $product->short_description }}</p>
            @endif

            @if ($composition->isNotEmpty())
                <h3 class="label-caps mt-6 border-b border-line pb-2 text-ink-muted">Composition</h3>
                <ul class="divide-y divide-line">
                    @foreach ($composition as $line)
                        <li class="py-2">{{ $line }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div>
            {{-- The wrapper is what gets observed: an element clipped by its own clip-path never counts as visible --}}
            <div data-reveal>
                <div class="ticket inline-block bg-ticket px-4 py-3">
                    <p class="label-caps">Prix net</p>
                    <x-ui.price :amount="$product->final_price" :original="$original" size="xl" />
                </div>
            </div>

            <p class="mt-3 flex items-center gap-2 text-sm {{ $product->stock > 0 ? ($lowStock ? 'text-amber' : 'text-ink-muted') : 'text-danger' }}">
                @if ($product->stock <= 0)
                    <x-ui.icon name="alert" class="size-4" /> Épuisé pour le moment
                @elseif ($lowStock)
                    <x-ui.icon name="alert" class="size-4" /> Plus que {{ $product->stock }} en stock
                @else
                    <x-ui.icon name="check" class="size-4" /> En stock
                @endif
            </p>

            <x-storefront.add-to-cart :product="$product" class="mt-4 max-w-md" />
        </div>
    </div>
</article>
