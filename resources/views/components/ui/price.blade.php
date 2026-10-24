@props([
    'amount',
    'original' => null,   // list price, shown struck through when it is higher than the amount
    'size' => 'md',       // md | lg | xl
])

{{--
    The price as it would read on a shelf ticket: tabular figures, whole francs.
    A discount is never carried by colour alone: the old price is struck through and announced.
    Amounts always come from the server (Product::final_price, CartService), never from the view.
--}}
@php
    $sizes = [
        'md' => 'text-lg',
        'lg' => 'text-3xl',
        'xl' => 'text-5xl sm:text-6xl',
    ];
    $hasDiscount = $original !== null && (float) $original > (float) $amount;
    $number = number_format((float) $amount, 0, ',', "\u{00A0}");
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex flex-wrap items-baseline gap-x-3 tnum']) }}>
    @if ($hasDiscount)<span class="sr-only">{{ __('Prix actuel :') }}</span>@endif
    <span class="font-bold leading-none text-ink {{ $sizes[$size] ?? $sizes['md'] }}" style="font-stretch: 75%">{{ $number }}<span class="ml-1 text-[0.5em] font-semibold tracking-wide">FCFA</span></span>
    @if ($hasDiscount)
        <span class="sr-only">{{ __('au lieu de') }}</span>
        <s class="text-base text-ink-muted">{{ format_fcfa($original) }}</s>
    @endif
</span>
