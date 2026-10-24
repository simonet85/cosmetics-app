@props(['banner', 'eager' => false])

{{--
    A banner managed in the admin: text and picture side by side, one button. Never a carousel.
    The title keeps the line breaks the admin typed; the button appears only when the banner has a link.
--}}
@php
    $hasLink = filled($banner->link_url);
    $label = filled($banner->tr('button_text')) ? $banner->tr('button_text') : __('Découvrir');
@endphp

<section {{ $attributes->merge(['class' => 'grid border border-ink bg-mint md:grid-cols-2']) }} aria-labelledby="banner-{{ $banner->id }}">
    <div class="order-2 flex flex-col justify-center gap-3 p-6 sm:p-10 md:order-1">
        @if (filled($banner->tr('subtitle')))<p class="label-caps text-green-deep">{{ $banner->tr('subtitle') }}</p>@endif
        <h2 id="banner-{{ $banner->id }}" class="whitespace-pre-line text-4xl uppercase sm:text-5xl">{{ $banner->tr('title') }}</h2>
        @if (filled($banner->tr('description')))<p class="max-w-prose text-ink-muted">{{ $banner->tr('description') }}</p>@endif
        @if ($hasLink)
            <div class="mt-2"><x-ui.button :href="$banner->link_url">{{ $label }}<span class="sr-only"> : {{ $banner->tr('title') }}</span></x-ui.button></div>
        @endif
    </div>
    <div class="order-1 bg-photo md:order-2">
        <img src="{{ asset($banner->image_path) }}" alt="" width="800" height="450"
             loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async" class="aspect-[16/9] h-full w-full object-cover md:aspect-auto">
    </div>
</section>
