@props(['product'])

{{--
    One product on the shelf: photo, name in condensed capitals, net price, stock warning, add to cart.
    Discount percentage is computed from the real list and final prices, never stored in the view.
--}}
@php
    $final = (float) $product->final_price;
    $list = (float) $product->price;
    $discount = $list > 0 && $final < $list ? (int) round((1 - $final / $list) * 100) : 0;
    $lowStock = $product->stock > 0 && $product->stock <= 5;
@endphp

<article class="relative flex h-full flex-col border-b border-r border-line bg-white">
    <a href="{{ route('products.show', $product->slug) }}" class="relative block bg-photo" tabindex="-1" aria-hidden="true">
        <x-storefront.product-image :product="$product" sizes="(min-width: 1024px) 28vw, 50vw" class="aspect-[3/4] object-contain" />
        @if ($discount > 0 || $lowStock)
            <span class="absolute left-2 top-2 flex flex-col items-start gap-1">
                @if ($discount > 0)<x-ui.badge tone="ticket">-{{ $discount }} %</x-ui.badge>@endif
                @if ($lowStock)<x-ui.badge tone="danger">{{ __('Plus que :stock', ['stock' => $product->stock]) }}</x-ui.badge>@endif
            </span>
        @endif
    </a>

    <div class="flex flex-1 flex-col gap-2 p-3 sm:p-4">
        @if ($product->categories->isNotEmpty())
            <p class="label-caps text-ink-muted">{{ $product->categories->first()->name }}</p>
        @endif
        <h3 class="text-lg uppercase leading-tight sm:text-xl">
            <a href="{{ route('products.show', $product->slug) }}" class="inline-block py-1 hover:underline">{{ $product->name }}</a>
        </h3>
        <x-ui.price :amount="$final" :original="$discount > 0 ? $list : null" size="lg" class="mt-auto" />
        <x-storefront.add-to-cart :product="$product" :label="__('Ajouter')" class="mt-2" />
    </div>
</article>
