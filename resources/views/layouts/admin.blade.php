@php
    $user = auth()->user();
    $unreadMessages = \App\Models\ContactMessage::unread()->count();

    // [label, route, icon, active pattern, indent]
    $menu = [
        ['Tableau de bord', 'admin.dashboard', 'fa-home', 'admin.dashboard', false],
        ['Produits', 'admin.products.index', 'fa-box', 'admin.products.*', false],
        ['Catégories', 'admin.categories.index', 'fa-tag', 'admin.categories.*', true],
        ['Commandes', 'admin.orders.index', 'fa-shopping-cart', 'admin.orders.*', false],
        ['Clients', 'admin.customers.index', 'fa-users', 'admin.customers.*', false],
        ['Avis', 'admin.reviews.index', 'fa-star', 'admin.reviews.*', false],
        ['Coupons', 'admin.coupons.index', 'fa-ticket-alt', 'admin.coupons.*', false],
        ['Bannières', 'admin.banners.index', 'fa-image', 'admin.banners.*', false],
        ['Newsletter', 'admin.newsletter.index', 'fa-envelope', 'admin.newsletter.*', false],
        ['Messages', 'admin.messages.index', 'fa-comments', 'admin.messages.*', false],
    ];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Tableau de bord') - Admin {{ config('app.name') }}</title>

    {{-- Icons are served from this site (no third-party font or icon host) --}}
    <link rel="stylesheet" href="{{ asset('vendors/fontawesome/css/all.min.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="min-h-dvh bg-gray-100 font-sans text-gray-900">
    <a href="#content" class="skip-link">Aller au contenu</a>

    {{-- Top bar --}}
    <header class="fixed top-0 z-50 w-full border-b border-gray-200 bg-white">
        <div class="flex h-16 items-center justify-between gap-4 px-4">
            <div class="flex items-center gap-3">
                <button type="button" id="sidebarToggle" aria-controls="sidebar" aria-expanded="false"
                        class="inline-flex size-11 cursor-pointer items-center justify-center text-gray-700 hover:text-green-deep lg:hidden">
                    <i class="fas fa-bars text-xl" aria-hidden="true"></i>
                    <span class="sr-only">Ouvrir le menu</span>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="flex min-h-11 items-center gap-2">
                    <span class="text-2xl font-bold" style="font-stretch: 75%">GLOWING</span>
                    <span class="bg-green px-2 py-1 text-xs font-semibold text-white">Admin</span>
                </a>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-2 px-2 text-gray-700 hover:text-green-deep">
                    <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                    <span class="hidden md:inline">Voir le site</span>
                    <span class="sr-only md:hidden">Voir le site</span><span class="sr-only"> (nouvel onglet)</span>
                </a>

                {{-- Account menu: a native disclosure, usable with the keyboard --}}
                <details class="relative" id="accountMenu">
                    <summary class="flex min-h-11 cursor-pointer list-none items-center gap-2 px-2 text-gray-700 hover:text-green-deep">
                        <i class="fas fa-user-circle text-2xl" aria-hidden="true"></i>
                        <span class="hidden md:block">{{ $user->first_name }}</span>
                        <i class="fas fa-chevron-down text-xs" aria-hidden="true"></i>
                        <span class="sr-only md:hidden">Mon compte</span>
                    </summary>
                    <div class="absolute right-0 mt-1 w-52 border border-gray-300 bg-white">
                        <a href="{{ route('account.profile') }}" class="flex min-h-11 items-center gap-2 px-4 text-gray-700 hover:bg-gray-50"><i class="fas fa-user w-4" aria-hidden="true"></i>Mon profil</a>
                        <a href="{{ route('admin.settings.index') }}" class="flex min-h-11 items-center gap-2 px-4 text-gray-700 hover:bg-gray-50"><i class="fas fa-cog w-4" aria-hidden="true"></i>Paramètres</a>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-200">
                            @csrf
                            <button type="submit" class="flex min-h-11 w-full cursor-pointer items-center gap-2 px-4 text-left text-red-700 hover:bg-gray-50"><i class="fas fa-sign-out-alt w-4" aria-hidden="true"></i>Déconnexion</button>
                        </form>
                    </div>
                </details>
            </div>
        </div>
    </header>

    {{-- Sidebar --}}
    <aside id="sidebar" class="on-dark fixed left-0 top-0 z-40 h-full w-64 -translate-x-full bg-gray-900 pt-16 text-white transition-transform duration-300 lg:translate-x-0">
        <nav aria-label="Administration" class="h-full overflow-y-auto px-3 py-5">
            <ul class="space-y-1">
                @foreach ($menu as [$label, $route, $icon, $pattern, $indent])
                    @php $active = request()->routeIs($pattern); @endphp
                    <li>
                        <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                           class="flex min-h-11 items-center gap-3 px-4 {{ $indent ? 'pl-10 text-sm' : '' }} hover:bg-gray-800 {{ $active ? 'bg-gray-800 font-semibold' : '' }}">
                            <i class="fas {{ $icon }} w-5" aria-hidden="true"></i>
                            <span>{{ $label }}</span>
                            @if ($route === 'admin.messages.index' && $unreadMessages > 0)
                                <span class="ml-auto bg-green px-2 py-0.5 text-xs font-semibold text-white">{{ $unreadMessages }}<span class="sr-only"> non lus</span></span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
            <hr class="my-4 border-gray-700">
            <ul>
                <li>
                    <a href="{{ route('admin.settings.index') }}" @if (request()->routeIs('admin.settings.*')) aria-current="page" @endif
                       class="flex min-h-11 items-center gap-3 px-4 hover:bg-gray-800 {{ request()->routeIs('admin.settings.*') ? 'bg-gray-800 font-semibold' : '' }}">
                        <i class="fas fa-cog w-5" aria-hidden="true"></i>
                        <span>Paramètres</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    {{-- Page --}}
    <main id="content" tabindex="-1" class="pt-16 lg:ml-64">
        <div class="p-4 sm:p-6">
            @if (session('success'))
                <div role="status" class="mb-6 flex items-start justify-between gap-3 border border-green-700 bg-green-50 px-4 py-3 text-green-900">
                    <p class="flex items-start gap-2"><i class="fas fa-check-circle mt-1" aria-hidden="true"></i><span>{{ session('success') }}</span></p>
                    <button type="button" data-dismiss class="inline-flex size-8 shrink-0 cursor-pointer items-center justify-center hover:bg-green-100"><i class="fas fa-times" aria-hidden="true"></i><span class="sr-only">Fermer ce message</span></button>
                </div>
            @endif

            @if (session('error'))
                <div role="alert" class="mb-6 flex items-start justify-between gap-3 border border-red-700 bg-red-50 px-4 py-3 text-red-900">
                    <p class="flex items-start gap-2"><i class="fas fa-exclamation-circle mt-1" aria-hidden="true"></i><span>{{ session('error') }}</span></p>
                    <button type="button" data-dismiss class="inline-flex size-8 shrink-0 cursor-pointer items-center justify-center hover:bg-red-100"><i class="fas fa-times" aria-hidden="true"></i><span class="sr-only">Fermer ce message</span></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    {{-- Sidebar overlay (small screens) --}}
    <div id="sidebarOverlay" class="fixed inset-0 z-30 hidden bg-black/50 lg:hidden"></div>

    @stack('scripts')

    <script>
        (function () {
            const toggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');

            function setOpen(open) {
                sidebar.classList.toggle('-translate-x-full', !open);
                overlay.classList.toggle('hidden', !open);
                toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
            }

            toggle?.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
            overlay?.addEventListener('click', () => setOpen(false));
            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                setOpen(false);
                document.getElementById('accountMenu')?.removeAttribute('open');
            });

            // The account menu closes when the click lands elsewhere
            document.addEventListener('click', (event) => {
                const menu = document.getElementById('accountMenu');
                if (menu && !menu.contains(event.target)) menu.removeAttribute('open');
            });

            document.querySelectorAll('[data-dismiss]').forEach((button) => {
                button.addEventListener('click', () => button.parentElement.remove());
            });
        })();
    </script>
</body>
</html>
