@props(['name', 'filled' => false])

{{--
    One icon set, one stroke (1.75). Decorative by default (aria-hidden): the control that contains the icon
    carries the accessible name. Paths are authored for a 24px grid.
--}}
@php
    $paths = [
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'search' => '<circle cx="11" cy="11" r="7.5"/><path d="m20.5 20.5-4-4"/>',
        'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'heart' => '<path d="M19 14c1.5-1.5 3-3.2 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.8 0-3 .5-4.5 2-1.5-1.5-2.7-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4 3 5.5l7 7Z"/>',
        'bag' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'truck' => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.7a1 1 0 0 0-.2-.6l-3.5-4.4A1 1 0 0 0 17.5 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
        'smartphone' => '<rect width="14" height="20" x="5" y="2" rx="2"/><path d="M12 18h.01"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'plus' => '<path d="M5 12h14M12 5v14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'star' => '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1L12 2Z"/>',
        'alert' => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'size-5 shrink-0']) }}
     viewBox="0 0 24 24" fill="{{ $filled ? 'currentColor' : 'none' }}" stroke="currentColor"
     stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $paths[$name] ?? '' !!}</svg>
