{{-- Data (navCategories, shippingPromise, paymentLabels, cartCount, wishlistCount) comes from StorefrontComposer. --}}
@php
    $links = [
        ['label' => 'Boutique', 'href' => route('shop.index'), 'active' => (request()->routeIs('shop.*') && ! request()->routeIs('shop.category')) || request()->routeIs('products.*')],
        ['label' => 'À propos', 'href' => route('pages.about'), 'active' => request()->routeIs('pages.about')],
        ['label' => 'Contact', 'href' => route('pages.contact'), 'active' => request()->routeIs('pages.contact')],
    ];
    // The rayon being browsed, from /shop/category/{slug} or ?category={slug}
    $currentRayon = request()->routeIs('shop.category') ? (string) optional(request()->route('category'))->slug : (string) request()->query('category');
    $iconButton = 'relative inline-flex size-11 shrink-0 items-center justify-center text-ink hover:text-green-deep';
@endphp

{{-- What the shop promises, straight from the settings --}}
<div class="on-dark bg-green text-white">
    <div class="wrap flex flex-wrap items-center justify-center gap-x-6 gap-y-1 py-2 text-sm font-medium">
        <p class="flex items-center gap-2">
            <x-ui.icon name="truck" class="size-4" />
            <span>{{ $shippingPromise }}</span>
        </p>
        @if ($paymentLabels)
            <p class="hidden items-center gap-2 sm:flex">
                <x-ui.icon name="smartphone" class="size-4" />
                <span>Paiement : {{ implode(', ', array_map('mb_strtolower', $paymentLabels)) }}</span>
            </p>
        @endif
    </div>
</div>

<header class="sticky top-0 z-10 border-b border-line bg-white">
    <div class="wrap flex h-16 items-center gap-2">
        <button type="button" class="{{ $iconButton }} -ml-2 lg:hidden" data-dialog-open="nav-drawer" aria-controls="nav-drawer">
            <x-ui.icon name="menu" class="size-6" />
            <span class="sr-only">Ouvrir le menu</span>
        </button>

        <a href="{{ route('home') }}" class="mr-auto flex min-h-11 items-center lg:mr-8">
            <img src="{{ asset('images/others/logo-black-02.png') }}" alt="{{ config('app.name', 'Glowing Cosmetics') }}" width="105" height="28" class="h-7 w-auto">
        </a>

        <nav aria-label="Navigation principale" class="mr-auto hidden lg:block">
            <ul class="flex items-center gap-1">
                @foreach ($links as $link)
                    <li>
                        <a href="{{ $link['href'] }}" @if ($link['active']) aria-current="page" @endif
                           class="inline-flex min-h-11 items-center px-3 font-semibold hover:text-green-deep {{ $link['active'] ? 'underline decoration-2 decoration-green underline-offset-8' : '' }}">{{ $link['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <button type="button" class="{{ $iconButton }}" data-dialog-open="search-sheet" aria-controls="search-sheet">
            <x-ui.icon name="search" class="size-6" />
            <span class="sr-only">Rechercher</span>
        </button>

        <a href="{{ auth()->check() ? route('account.dashboard') : route('login') }}" class="{{ $iconButton }}">
            <x-ui.icon name="user" class="size-6" />
            <span class="sr-only">{{ auth()->check() ? 'Mon compte' : 'Se connecter' }}</span>
        </a>

        <a href="{{ route('wishlist.index') }}" class="{{ $iconButton }}">
            <x-ui.icon name="heart" class="size-6" />
            <span class="sr-only">Mes favoris</span>
            <span data-count="wishlist" aria-hidden="true" @if (! $wishlistCount) hidden @endif
                  class="tnum absolute right-0.5 top-0.5 grid min-w-5 place-items-center bg-green px-1 text-xs font-bold leading-5 text-white">{{ $wishlistCount }}</span>
        </a>

        <a href="{{ route('cart.index') }}" class="{{ $iconButton }}">
            <x-ui.icon name="bag" class="size-6" />
            <span class="sr-only">Mon panier</span>
            <span data-count="cart" aria-hidden="true" @if (! $cartCount) hidden @endif
                  class="tnum absolute right-0.5 top-0.5 grid min-w-5 place-items-center bg-green px-1 text-xs font-bold leading-5 text-white">{{ $cartCount }}</span>
        </a>
    </div>
</header>

{{-- Rayons: always visible from lg up (below that they live in the drawer) --}}
@if ($navCategories->isNotEmpty())
    <nav aria-label="Rayons" class="hidden border-b border-line bg-white lg:block">
        <ul class="wrap flex items-center gap-1">
            <li class="label-caps pr-3 text-ink-muted">Rayons</li>
            @foreach ($navCategories as $category)
                <li>
                    <a href="{{ route('shop.category', $category->slug) }}" @if ($currentRayon === $category->slug) aria-current="page" @endif
                       class="inline-flex min-h-11 items-center px-3 hover:text-green-deep {{ $currentRayon === $category->slug ? 'font-semibold underline decoration-2 decoration-green underline-offset-8' : 'text-ink-muted' }}">{{ $category->name }}</a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif

{{-- Navigation drawer (small screens) --}}
<dialog id="nav-drawer" class="drawer" aria-label="Menu">
    <div class="flex h-full flex-col">
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-line px-4">
            <img src="{{ asset('images/others/logo-black-02.png') }}" alt="" width="105" height="28" class="h-7 w-auto">
            <button type="button" class="{{ $iconButton }} -mr-2" data-dialog-close>
                <x-ui.icon name="close" class="size-6" />
                <span class="sr-only">Fermer le menu</span>
            </button>
        </div>

        <nav aria-label="Menu" class="grow overflow-y-auto px-4 py-4">
            <ul class="grid">
                <li><a href="{{ route('home') }}" class="flex min-h-12 items-center border-b border-line text-lg font-semibold">Accueil</a></li>
                @foreach ($links as $link)
                    <li><a href="{{ $link['href'] }}" @if ($link['active']) aria-current="page" @endif class="flex min-h-12 items-center border-b border-line text-lg font-semibold">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>

            @if ($navCategories->isNotEmpty())
                <p class="label-caps mt-6 text-ink-muted">Rayons</p>
                <ul class="grid">
                    @foreach ($navCategories as $category)
                        <li><a href="{{ route('shop.category', $category->slug) }}" @if ($currentRayon === $category->slug) aria-current="page" @endif
                               class="flex min-h-12 items-center border-b border-line {{ $currentRayon === $category->slug ? 'font-semibold' : 'text-ink-muted' }}">{{ $category->name }}</a></li>
                    @endforeach
                </ul>
            @endif

            <ul class="mt-6 grid gap-1 text-base">
                @auth
                    <li><a href="{{ route('account.orders') }}" class="flex min-h-11 items-center">Mes commandes</a></li>
                    <li><a href="{{ route('account.dashboard') }}" class="flex min-h-11 items-center">Mon compte</a></li>
                    @if (auth()->user()->isAdmin())
                        <li><a href="{{ route('admin.dashboard') }}" class="flex min-h-11 items-center">Administration</a></li>
                    @endif
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex min-h-11 cursor-pointer items-center text-ink-muted">Se déconnecter</button>
                        </form>
                    </li>
                @else
                    <li><a href="{{ route('login') }}" class="flex min-h-11 items-center">Se connecter</a></li>
                    <li><a href="{{ route('register') }}" class="flex min-h-11 items-center">Créer un compte</a></li>
                @endauth
            </ul>
        </nav>
    </div>
</dialog>

{{-- Search --}}
<dialog id="search-sheet" class="sheet" aria-label="Rechercher">
    <form action="{{ route('shop.index') }}" method="GET" role="search" class="wrap flex items-end gap-3 py-5">
        <div class="grid grow gap-1.5">
            <label for="search-input" class="text-sm font-semibold">Rechercher un produit</label>
            <input id="search-input" name="search" type="search" autocomplete="off" autofocus
                   class="min-h-12 w-full rounded-sm border border-field bg-white px-3 text-base">
        </div>
        <x-ui.button type="submit">Rechercher</x-ui.button>
        <button type="button" class="{{ $iconButton }}" data-dialog-close>
            <x-ui.icon name="close" class="size-6" />
            <span class="sr-only">Fermer la recherche</span>
        </button>
    </form>
</dialog>
