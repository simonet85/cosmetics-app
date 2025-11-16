<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - Admin GLOWING</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="bg-gray-100 font-sans">

    {{-- Top Navigation Bar --}}
    <nav class="bg-white border-b border-gray-200 fixed w-full top-0 z-50">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                {{-- Left: Logo & Menu Toggle --}}
                <div class="flex items-center gap-4">
                    <button id="sidebarToggle" class="text-gray-600 hover:text-gray-900 lg:hidden">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                        <span class="text-2xl font-bold text-gray-900">GLOWING</span>
                        <span class="px-2 py-1 bg-[#5a7c6f] text-white text-xs rounded">Admin</span>
                    </a>
                </div>

                {{-- Right: User Menu --}}
                <div class="flex items-center gap-4">
                    {{-- View Site --}}
                    <a href="{{ route('home') }}" target="_blank" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-external-link-alt"></i>
                        <span class="ml-2 hidden md:inline">Voir le site</span>
                    </a>

                    {{-- User Dropdown --}}
                    <div class="relative group">
                        <button class="flex items-center gap-2 text-gray-700 hover:text-gray-900">
                            <i class="fas fa-user-circle text-2xl"></i>
                            <span class="hidden md:block">{{ auth()->user()->name }}</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                            <a href="{{ route('account.profile') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-user mr-2"></i>Mon profil
                            </a>
                            <a href="{{ route('admin.settings.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-cog mr-2"></i>Paramètres
                            </a>
                            <hr class="my-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-red-600 hover:bg-gray-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    {{-- Sidebar --}}
    <aside id="sidebar" class="fixed left-0 top-0 w-64 h-full bg-gray-900 text-white pt-16 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-40">
        <div class="px-4 py-6 overflow-y-auto h-full">
            {{-- Navigation Menu --}}
            <nav class="space-y-2">
                {{-- Dashboard --}}
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800' : '' }}">
                    <i class="fas fa-home w-5"></i>
                    <span>Tableau de Bord</span>
                </a>

                {{-- Products --}}
                <div class="space-y-1">
                    <a href="{{ route('admin.products.index') }}" class="flex items-center gap-3 px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.products.*') ? 'bg-gray-800' : '' }}">
                        <i class="fas fa-box w-5"></i>
                        <span>Produits</span>
                    </a>
                    <a href="{{ route('admin.categories.index') }}" class="flex items-center gap-3 px-4 py-3 pl-12 text-sm rounded hover:bg-gray-800 {{ request()->routeIs('admin.categories.*') ? 'bg-gray-800' : '' }}">
                        <i class="fas fa-tag w-5"></i>
                        <span>Catégories</span>
                    </a>
                </div>

                {{-- Orders --}}
                <a href="{{ route('admin.orders.index') }}" class="flex items-center gap-3 px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.orders.*') ? 'bg-gray-800' : '' }}">
                    <i class="fas fa-shopping-cart w-5"></i>
                    <span>Commandes</span>
                </a>

                {{-- Customers --}}
                <a href="{{ route('admin.customers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.customers.*') ? 'bg-gray-800' : '' }}">
                    <i class="fas fa-users w-5"></i>
                    <span>Clients</span>
                </a>

                {{-- Reviews --}}
                <a href="{{ route('admin.reviews.index') }}" class="flex items-center gap-3 px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.reviews.*') ? 'bg-gray-800' : '' }}">
                    <i class="fas fa-star w-5"></i>
                    <span>Avis</span>
                </a>

                {{-- Coupons --}}
                <a href="{{ route('admin.coupons.index') }}" class="flex items-center gap-3 px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.coupons.*') ? 'bg-gray-800' : '' }}">
                    <i class="fas fa-ticket-alt w-5"></i>
                    <span>Coupons</span>
                </a>

                {{-- Banners --}}
                <a href="{{ route('admin.banners.index') }}" class="flex items-center gap-3 px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.banners.*') ? 'bg-gray-800' : '' }}">
                    <i class="fas fa-image w-5"></i>
                    <span>Bannières</span>
                </a>

                {{-- Newsletter --}}
                <a href="{{ route('admin.newsletter.index') }}" class="flex items-center gap-3 px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.newsletter.*') ? 'bg-gray-800' : '' }}">
                    <i class="fas fa-envelope w-5"></i>
                    <span>Newsletter</span>
                </a>

                <hr class="my-4 border-gray-700">

                {{-- Settings --}}
                <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-4 py-3 rounded hover:bg-gray-800 {{ request()->routeIs('admin.settings.*') ? 'bg-gray-800' : '' }}">
                    <i class="fas fa-cog w-5"></i>
                    <span>Paramètres</span>
                </a>
            </nav>
        </div>
    </aside>

    {{-- Main Content --}}
    <main class="lg:ml-64 pt-16">
        <div class="p-6">
            {{-- Flash Messages --}}
            @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-6 flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif

            @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-6 flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif

            @yield('content')
        </div>
    </main>

    {{-- Sidebar Overlay (Mobile) --}}
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

    @stack('scripts')

    <script>
        // Sidebar toggle for mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        }

        sidebarToggle?.addEventListener('click', toggleSidebar);
        sidebarOverlay?.addEventListener('click', toggleSidebar);
    </script>
</body>
</html>
