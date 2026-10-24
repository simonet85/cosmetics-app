@props(['title', 'updated' => null, 'width' => 'max-w-3xl'])

{{-- Text page (about, FAQ, legal): breadcrumb, h1, a readable column. Long text is styled by .info-prose. --}}
<div class="wrap py-6 sm:py-10">
    <nav aria-label="Fil d'Ariane" class="flex flex-wrap items-center text-sm text-ink-muted">
        <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center underline">Accueil</a>
        <span aria-hidden="true" class="mx-2">/</span>
        <span aria-current="page" class="text-ink">{{ $title }}</span>
    </nav>

    <div class="{{ $width }} pb-10">
        <h1 class="mt-2 text-4xl uppercase sm:text-5xl">{{ $title }}</h1>
        @if ($updated)<p class="mt-2 text-ink-muted">Dernière mise à jour : {{ $updated }}</p>@endif

        <div class="info-prose mt-8">{{ $slot }}</div>
    </div>
</div>
