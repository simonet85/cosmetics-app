@props(['product', 'full' => true, 'label' => 'Ajouter au panier'])

{{-- Posts to cart.add; resources/js/storefront.js intercepts it and updates the counter without reloading. --}}
<form method="POST" action="{{ route('cart.add') }}" data-add-to-cart {{ $attributes }}>
    @csrf
    <input type="hidden" name="product_id" value="{{ $product->id }}">
    <input type="hidden" name="quantity" value="1">
    @php $width = $full ? 'w-full' : ''; @endphp
    @if ($product->stock > 0)
        <x-ui.button type="submit" :class="$width" :aria-label="$label === 'Ajouter au panier' ? null : $label.' '.$product->name.' au panier'">
            <x-ui.icon name="bag" class="size-5" />
            <span data-label>{{ $label }}</span>
        </x-ui.button>
    @else
        <x-ui.button type="button" variant="secondary" disabled :class="$width">Épuisé</x-ui.button>
    @endif
</form>
