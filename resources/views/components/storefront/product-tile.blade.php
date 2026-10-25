@props(['product', 'inWishlist' => false])

{{--
    One product of the Glowing Beauty shop list: photo with status badges, name, rayons, net price, add to cart, favourites.
    Badges are computed from real data: HOT (is_hot), NEW (added in the last 30 days), -N % (list vs final price),
    SOLD OUT (no stock). The text says it, the colour only supports it.
--}}
@php
    $final = (float) $product->final_price;
    $list = (float) $product->price;
    $discount = $list > 0 && $final < $list ? (int) round((1 - $final / $list) * 100) : 0;
    $soldOut = $product->stock <= 0;
    $isNew = $product->created_at?->gt(now()->subDays(30));
    $rayons = $product->categories->map(fn ($category) => $category->tr('name'))->implode(', ');
@endphp

<article class="relative flex h-full flex-col text-center">
    <a href="{{ route('products.show', $product->slug) }}" class="relative block bg-photo" tabindex="-1" aria-hidden="true">
        <x-storefront.product-image :product="$product" sizes="(min-width: 1024px) 24vw, 50vw" class="aspect-square object-contain" />
        <span class="absolute left-3 top-3 flex flex-col items-start gap-1.5">
            @if ($product->is_hot)<span class="label-caps bg-hot px-2.5 py-1.5 leading-none text-white">{{ __('Hot') }}</span>@endif
            @if ($isNew)<span class="label-caps bg-new px-2.5 py-1.5 leading-none text-white">{{ __('Nouveau') }}</span>@endif
            @if ($discount > 0)<span class="label-caps bg-green px-2.5 py-1.5 leading-none text-white">-{{ $discount }} %</span>@endif
            @if ($soldOut)<span class="label-caps border border-ink bg-white px-2.5 py-1.5 leading-none text-ink">{{ __('Épuisé') }}</span>@endif
        </span>
    </a>

    <div class="flex flex-1 flex-col items-center gap-1 px-2 pt-4">
        <h3 class="text-base sm:text-lg">
            <a href="{{ route('products.show', $product->slug) }}" class="inline-block py-1 hover:underline">{{ $product->tr('name') }}</a>
        </h3>
        @if ($rayons !== '')<p class="text-sm text-ink-muted">{{ $rayons }}</p>@endif
        <x-ui.price :amount="$final" :original="$discount > 0 ? $list : null" class="mt-1 justify-center" />
    </div>

    <div class="mt-4 flex flex-col items-center gap-1 px-2">
        <x-storefront.add-to-cart :product="$product" :full="false" :label="__('Ajouter')" />
        <button type="button" data-wishlist-toggle="{{ $product->id }}" aria-pressed="{{ $inWishlist ? 'true' : 'false' }}"
                class="group inline-flex min-h-11 cursor-pointer items-center gap-2 px-2 text-sm hover:text-green-deep">
            <x-ui.icon name="heart" class="size-5 group-aria-pressed:fill-current" />
            <span>{{ __('Mettre dans mes favoris') }}</span>
        </button>
    </div>
</article>
