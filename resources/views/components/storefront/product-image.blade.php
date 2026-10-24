@props(['product', 'sizes' => '(min-width: 1024px) 40vw, 100vw', 'eager' => false])

{{-- Real photo of the product, or the neutral placeholder. Dimensions are declared to avoid layout shift. --}}
@php
    $path = $product->primary_image?->path ?? 'images/products/product-placeholder.jpg';
@endphp
<img src="{{ asset($path) }}" alt="{{ $product->name }}" width="330" height="440" sizes="{{ $sizes }}"
     loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async" @if ($eager) fetchpriority="high" @endif
     {{ $attributes->merge(['class' => 'h-auto w-full']) }}>
