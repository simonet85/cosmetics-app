@props([
    'variant' => 'primary',   // primary | secondary | text | inverse
    'href' => null,
    'type' => 'button',
])

{{--
    One primary action per screen. 48px tall (touch target), focus ring comes from the global :focus-visible.
    Renders a link when `href` is given, otherwise a button.
--}}
@php
    $base = 'inline-flex min-h-12 items-center justify-center gap-2 text-base font-semibold transition-colors duration-150 disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer';

    $variants = [
        'primary' => 'px-5 bg-green text-white hover:bg-green-deep rounded-sm',
        'secondary' => 'px-5 border border-ink text-ink hover:bg-ink hover:text-white rounded-sm',
        'inverse' => 'px-5 bg-white text-green-deep hover:bg-mint rounded-sm',
        'text' => 'px-0 text-green-deep underline decoration-1 hover:decoration-2 min-h-11',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
