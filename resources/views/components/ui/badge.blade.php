@props(['tone' => 'mint'])   {{-- mint | ticket | ink | danger --}}

{{-- Short status label. Never the only carrier of a meaning: the text says it, the colour only supports it. --}}
@php
    $tones = [
        'mint' => 'bg-mint text-green-deep',
        'ticket' => 'bg-ticket text-ink',
        'ink' => 'bg-ink text-white',
        'danger' => 'bg-white text-danger border border-danger',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'label-caps inline-flex items-center px-2 py-1 leading-none '.($tones[$tone] ?? $tones['mint'])]) }}>{{ $slot }}</span>
