@props(['title', 'current' => 'dashboard'])   {{-- current: dashboard | orders | profile --}}

{{-- Frame of the customer area: breadcrumb, h1, a small section menu, then the page in the slot. --}}
@php
    $tabs = [
        'dashboard' => ['Tableau de bord', route('account.dashboard')],
        'orders' => ['Mes commandes', route('account.orders')],
        'profile' => ['Mon profil', route('account.profile')],
    ];
@endphp

<div class="wrap py-6 sm:py-10">
    <nav aria-label="Fil d'Ariane" class="flex flex-wrap items-center text-sm text-ink-muted">
        <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center underline">Accueil</a>
        <span aria-hidden="true" class="mx-2">/</span>
        @if ($current === 'dashboard')
            <span aria-current="page" class="text-ink">Mon compte</span>
        @else
            <a href="{{ route('account.dashboard') }}" class="inline-flex min-h-11 items-center underline">Mon compte</a>
            <span aria-hidden="true" class="mx-2">/</span>
            <span aria-current="page" class="text-ink">{{ $tabs[$current][0] ?? $title }}</span>
        @endif
    </nav>

    <h1 class="mt-2 text-4xl uppercase sm:text-5xl">{{ $title }}</h1>

    <nav aria-label="Mon compte" class="mt-6 border-b border-ink">
        <ul class="flex flex-wrap gap-x-6">
            @foreach ($tabs as $key => [$label, $href])
                <li>
                    <a href="{{ $href }}" @if ($current === $key) aria-current="page" @endif
                       class="inline-flex min-h-12 items-center border-b-4 font-semibold {{ $current === $key ? 'border-green' : 'border-transparent text-ink-muted hover:text-green-deep' }}">{{ $label }}</a>
                </li>
            @endforeach
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex min-h-12 cursor-pointer items-center border-b-4 border-transparent text-ink-muted hover:text-green-deep">Se déconnecter</button>
                </form>
            </li>
        </ul>
    </nav>

    @if (session('success'))
        <p role="status" class="mt-6 border border-green bg-mint px-4 py-3 font-semibold">{{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p role="alert" class="mt-6 flex items-start gap-2 border border-danger px-4 py-3 font-semibold text-danger"><x-ui.icon name="alert" class="mt-0.5 size-5 shrink-0" /> {{ session('error') }}</p>
    @endif

    <div class="mt-8">{{ $slot }}</div>
</div>
