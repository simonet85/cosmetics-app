@props(['tone' => 'success', 'title', 'icon' => 'check'])   {{-- success | warning | danger --}}

{{-- A short outcome page (payment received, failed, error): one message, then the next step in the slot. --}}
@php
    $tones = [
        'success' => 'border-green bg-mint',
        'warning' => 'border-amber bg-ticket/40',
        'danger' => 'border-danger bg-white',
    ];
    $iconTone = ['success' => 'text-green-deep', 'warning' => 'text-amber', 'danger' => 'text-danger'];
@endphp

<div {{ $attributes->merge(['class' => 'mx-auto max-w-2xl border-2 p-6 sm:p-10 '.($tones[$tone] ?? $tones['success'])]) }}>
    <x-ui.icon :name="$icon" class="size-10 {{ $iconTone[$tone] ?? '' }}" />
    <h1 class="mt-4 text-4xl uppercase sm:text-5xl">{{ $title }}</h1>
    <div class="mt-4 grid gap-4">{{ $slot }}</div>
</div>
