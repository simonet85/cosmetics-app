@props(['dark' => false])

{{-- One link per language the shop offers; the choice is remembered in a cookie (LocaleController). --}}
@php $current = app()->getLocale(); @endphp
<nav aria-label="{{ __('Langue') }}" {{ $attributes }}>
    <ul class="flex items-center">
        @foreach (config('locales.available') as $code => $meta)
            <li>
                <a href="{{ route('locale.switch', $code) }}" lang="{{ $code }}" hreflang="{{ $code }}" @if ($code === $current) aria-current="true" @endif
                   class="inline-flex min-h-11 min-w-11 items-center justify-center px-2 text-sm font-semibold uppercase {{ $code === $current ? ($dark ? 'underline decoration-2 decoration-white underline-offset-8' : 'underline decoration-2 decoration-green underline-offset-8') : ($dark ? 'text-white/80 hover:text-white' : 'text-ink-muted hover:text-green-deep') }}">
                    <span aria-hidden="true">{{ $code }}</span><span class="sr-only">{{ $meta['name'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
