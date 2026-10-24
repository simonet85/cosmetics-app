@props(['title', 'crumbs' => []])   {{-- crumbs: [['label' => 'Connexion', 'href' => '...'], ...] last one is the current page --}}

{{-- Frame shared by the sign-in, sign-up and password pages: breadcrumb, h1, one narrow column. --}}
<div class="wrap py-6 sm:py-10">
    <nav aria-label="Fil d'Ariane" class="flex flex-wrap items-center text-sm text-ink-muted">
        <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center underline">Accueil</a>
        @foreach ($crumbs as $crumb)
            <span aria-hidden="true" class="mx-2">/</span>
            @if (! empty($crumb['href']))
                <a href="{{ $crumb['href'] }}" class="inline-flex min-h-11 items-center underline">{{ $crumb['label'] }}</a>
            @else
                <span aria-current="page" class="text-ink">{{ $crumb['label'] }}</span>
            @endif
        @endforeach
    </nav>

    <div class="mx-auto mt-4 max-w-md pb-10">
        <h1 class="text-4xl uppercase sm:text-5xl">{{ $title }}</h1>

        @if (session('status'))
            <p role="status" class="mt-6 border border-green bg-mint px-4 py-3 font-semibold">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p role="alert" class="mt-6 flex items-start gap-2 border border-danger px-4 py-3 font-semibold text-danger"><x-ui.icon name="alert" class="mt-0.5 size-5 shrink-0" /> {{ session('error') }}</p>
        @endif

        <div class="mt-6 grid gap-5">{{ $slot }}</div>
    </div>
</div>
