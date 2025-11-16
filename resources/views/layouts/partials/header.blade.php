<header class="sticky top-0 z-50 bg-white shadow-sm">
    <div class="container-custom">
        <div class="flex items-center justify-between py-4">

            {{-- Mobile Menu Toggle --}}
            <div class="lg:hidden">
                <button id="mobile-menu-toggle" type="button" class="text-gray-900">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex-shrink-0">
                <span class="text-2xl font-bold text-gray-900">GLOWING</span>
            </a>

            {{-- Desktop Navigation --}}
            <nav class="hidden lg:flex space-x-8">
                <a href="{{ route('home') }}" class="text-gray-900 hover:text-[#5a7c6f] font-medium transition">Accueil</a>
                <a href="{{ route('shop.index') }}" class="text-gray-900 hover:text-[#5a7c6f] font-medium transition">Boutique</a>
                <a href="{{ route('pages.about') }}" class="text-gray-900 hover:text-[#5a7c6f] font-medium transition">À Propos</a>
                <a href="{{ route('pages.contact') }}" class="text-gray-900 hover:text-[#5a7c6f] font-medium transition">Contact</a>
            </nav>

            {{-- Header Actions --}}
            <div class="flex items-center space-x-6">
                {{-- Search --}}
                <button type="button" id="search-toggle" class="text-gray-900 hover:text-[#5a7c6f] transition">
                    <i class="fas fa-search text-xl"></i>
                </button>

                {{-- User Account --}}
                @auth
                    <div class="relative group">
                        @if(auth()->user()->avatar)
                            <button type="button" class="hover:opacity-80 transition">
                                <img src="{{ asset(auth()->user()->avatar) }}" alt="{{ auth()->user()->first_name }}"
                                     class="w-8 h-8 rounded-full object-cover border-2 border-gray-300 hover:border-[#5a7c6f] transition">
                            </button>
                        @else
                            <button type="button" class="text-gray-900 hover:text-[#5a7c6f] transition">
                                <i class="fas fa-user text-xl"></i>
                            </button>
                        @endif
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden group-hover:block z-50">
                            <a href="{{ route('account.dashboard') }}" class="block px-4 py-2 text-gray-900 hover:bg-gray-100">Tableau de Bord</a>
                            <a href="{{ route('account.orders') }}" class="block px-4 py-2 text-gray-900 hover:bg-gray-100">Mes Commandes</a>
                            <a href="{{ route('account.profile') }}" class="block px-4 py-2 text-gray-900 hover:bg-gray-100">Mon Profil</a>
                            @if(auth()->user()->isAdmin())
                                <hr class="my-2">
                                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-gray-900 hover:bg-gray-100">
                                    <i class="fas fa-shield-alt mr-2"></i>Administration
                                </a>
                            @endif
                            <hr class="my-2">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-gray-900 hover:bg-gray-100">Déconnexion</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="text-gray-900 hover:text-[#5a7c6f] transition">
                        <i class="fas fa-user text-xl"></i>
                    </a>
                @endauth

                {{-- Wishlist --}}
                <a href="{{ route('wishlist.index') }}" class="relative text-gray-900 hover:text-[#5a7c6f] transition">
                    <i class="fas fa-heart text-xl"></i>
                    <span id="wishlist-count" class="absolute -top-2 -right-2 bg-gray-900 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center {{ session('wishlist_count', 0) > 0 ? '' : 'hidden' }}">
                        {{ session('wishlist_count', 0) }}
                    </span>
                </a>

                {{-- Cart --}}
                <a href="{{ route('cart.index') }}" class="relative text-gray-900 hover:text-[#5a7c6f] transition">
                    <i class="fas fa-shopping-bag text-xl"></i>
                    <span id="cart-count" class="absolute -top-2 -right-2 bg-gray-900 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center {{ session('cart_count', 0) > 0 ? '' : 'hidden' }}">
                        {{ session('cart_count', 0) }}
                    </span>
                </a>
            </div>
        </div>
    </div>
</header>

{{-- Search Modal --}}
<div id="search-modal" class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden">
    <div class="container mx-auto px-4 py-20">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold">Rechercher</h3>
                <button id="search-close" type="button" class="text-gray-900 hover:text-gray-600">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <form action="{{ route('shop.index') }}" method="GET">
                <div class="relative">
                    <input type="text" name="search" placeholder="Rechercher des produits..."
                           class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                    <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-[#5a7c6f]">
                        <i class="fas fa-search text-xl"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Mobile Menu --}}
<div id="mobile-menu" class="fixed inset-0 z-50 bg-white transform -translate-x-full transition-transform duration-300 lg:hidden">
    <div class="flex items-center justify-between p-4 border-b">
        <span class="text-xl font-bold">Menu</span>
        <button id="mobile-menu-close" type="button" class="text-gray-900">
            <i class="fas fa-times text-2xl"></i>
        </button>
    </div>
    <nav class="p-4">
        <a href="{{ route('home') }}" class="block py-3 text-gray-900 hover:text-[#5a7c6f] font-medium transition">Accueil</a>
        <a href="{{ route('shop.index') }}" class="block py-3 text-gray-900 hover:text-[#5a7c6f] font-medium transition">Boutique</a>
        <a href="{{ route('pages.about') }}" class="block py-3 text-gray-900 hover:text-[#5a7c6f] font-medium transition">À Propos</a>
        <a href="{{ route('pages.contact') }}" class="block py-3 text-gray-900 hover:text-[#5a7c6f] font-medium transition">Contact</a>
    </nav>
</div>

@push('scripts')
<script>
    // Mobile menu toggle
    document.getElementById('mobile-menu-toggle').addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.remove('-translate-x-full');
    });

    document.getElementById('mobile-menu-close').addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.add('-translate-x-full');
    });

    // Search modal toggle
    document.getElementById('search-toggle').addEventListener('click', function() {
        document.getElementById('search-modal').classList.remove('hidden');
    });

    document.getElementById('search-close').addEventListener('click', function() {
        document.getElementById('search-modal').classList.add('hidden');
    });

    document.getElementById('search-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });

    // Update cart and wishlist counters dynamically
    function updateCounters() {
        fetch('/api/cart-wishlist-count')
            .then(response => response.json())
            .then(data => {
                const cartCount = document.getElementById('cart-count');
                const wishlistCount = document.getElementById('wishlist-count');

                if (data.cart_count > 0) {
                    cartCount.textContent = data.cart_count;
                    cartCount.classList.remove('hidden');
                } else {
                    cartCount.classList.add('hidden');
                }

                if (data.wishlist_count > 0) {
                    wishlistCount.textContent = data.wishlist_count;
                    wishlistCount.classList.remove('hidden');
                } else {
                    wishlistCount.classList.add('hidden');
                }
            });
    }

    // Update counters on page load
    updateCounters();

    // Update counters every 30 seconds
    setInterval(updateCounters, 30000);
</script>
@endpush
