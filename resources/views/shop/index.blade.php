@extends('layouts.app')

@section('content')

{{-- Page Header --}}
<div class="bg-gray-50 py-12 mb-12">
    <div class="container-custom">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Boutique</h1>
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">Boutique</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">
    <div class="flex flex-col lg:flex-row gap-8">

        {{-- Sidebar Filters --}}
        <aside class="w-full lg:w-64 flex-shrink-0">
            <div class="bg-white rounded-lg shadow-sm p-6 sticky top-24">
                <h3 class="text-lg font-semibold mb-6 text-gray-900">Filtres</h3>

                <form method="GET" action="{{ route('shop.index') }}" id="filter-form">

                    {{-- Search --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-900 mb-2">Recherche</label>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Rechercher des produits..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900"
                        >
                    </div>

                    {{-- Categories --}}
                    <div class="mb-6 pb-6 border-b border-gray-200">
                        <label class="block text-sm font-medium text-gray-900 mb-3">Catégories</label>
                        <div class="space-y-2">
                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="radio"
                                    name="category"
                                    value=""
                                    {{ request('category') == '' ? 'checked' : '' }}
                                    class="w-4 h-4 text-gray-900 focus:ring-gray-900"
                                    onchange="this.form.submit()"
                                >
                                <span class="ml-2 text-gray-700">Toutes les catégories</span>
                            </label>
                            @foreach($categories as $category)
                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="radio"
                                    name="category"
                                    value="{{ $category->slug }}"
                                    {{ request('category') == $category->slug ? 'checked' : '' }}
                                    class="w-4 h-4 text-gray-900 focus:ring-gray-900"
                                    onchange="this.form.submit()"
                                >
                                <span class="ml-2 text-gray-700">{{ $category->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Price Range --}}
                    <div class="mb-6 pb-6 border-b border-gray-200">
                        <label class="block text-sm font-medium text-gray-900 mb-3">Fourchette de prix</label>
                        <div class="flex gap-2 mb-3">
                            <input
                                type="number"
                                name="min_price"
                                value="{{ request('min_price', $priceRange->min ?? 0) }}"
                                placeholder="Min"
                                min="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 text-sm"
                            >
                            <span class="text-gray-500 self-center">-</span>
                            <input
                                type="number"
                                name="max_price"
                                value="{{ request('max_price', $priceRange->max ?? 1000) }}"
                                placeholder="Max"
                                min="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 text-sm"
                            >
                        </div>
                        <button type="submit" class="btn btn-primary w-full text-sm py-2">Appliquer</button>
                    </div>

                    {{-- Rating Filter --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-900 mb-3">Évaluation</label>
                        <div class="space-y-2">
                            @for($i = 5; $i >= 1; $i--)
                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="radio"
                                    name="rating"
                                    value="{{ $i }}"
                                    {{ request('rating') == $i ? 'checked' : '' }}
                                    class="w-4 h-4 text-gray-900 focus:ring-gray-900"
                                    onchange="this.form.submit()"
                                >
                                <span class="ml-2 flex items-center">
                                    @for($j = 1; $j <= 5; $j++)
                                        <i class="fas fa-star text-sm {{ $j <= $i ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                                    @endfor
                                    <span class="ml-1 text-sm text-gray-600">& Plus</span>
                                </span>
                            </label>
                            @endfor
                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="radio"
                                    name="rating"
                                    value=""
                                    {{ request('rating') == '' ? 'checked' : '' }}
                                    class="w-4 h-4 text-gray-900 focus:ring-gray-900"
                                    onchange="this.form.submit()"
                                >
                                <span class="ml-2 text-gray-700 text-sm">Toutes les notes</span>
                            </label>
                        </div>
                    </div>

                    {{-- Clear Filters --}}
                    @if(request()->hasAny(['category', 'min_price', 'max_price', 'rating', 'search', 'tag']))
                    <a href="{{ route('shop.index') }}" class="block text-center text-sm text-gray-600 hover:text-gray-900 underline">
                        Effacer tous les filtres
                    </a>
                    @endif

                </form>
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1">

            {{-- Toolbar --}}
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <p class="text-gray-600">
                    Affichage de <span class="font-semibold text-gray-900">{{ $products->firstItem() ?? 0 }}</span>
                    à <span class="font-semibold text-gray-900">{{ $products->lastItem() ?? 0 }}</span>
                    sur <span class="font-semibold text-gray-900">{{ $products->total() }}</span> résultats
                </p>

                <div class="flex items-center gap-4">
                    {{-- Sort By --}}
                    <form method="GET" action="{{ route('shop.index') }}" class="flex items-center gap-2">
                        @foreach(request()->except(['sort', 'per_page']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <label class="text-sm text-gray-700 whitespace-nowrap">Trier par:</label>
                        <select
                            name="sort"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 text-sm"
                            onchange="this.form.submit()"
                        >
                            <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Plus récents</option>
                            <option value="popularity" {{ request('sort') == 'popularity' ? 'selected' : '' }}>Popularité</option>
                            <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Évaluation</option>
                            <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Prix: Croissant</option>
                            <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Prix: Décroissant</option>
                            <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Nom: A à Z</option>
                            <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Nom: Z à A</option>
                        </select>
                    </form>

                    {{-- Per Page --}}
                    <form method="GET" action="{{ route('shop.index') }}" class="flex items-center gap-2">
                        @foreach(request()->except(['sort', 'per_page']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <label class="text-sm text-gray-700 whitespace-nowrap">Afficher:</label>
                        <select
                            name="per_page"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 text-sm"
                            onchange="this.form.submit()"
                        >
                            <option value="12" {{ request('per_page', 12) == 12 ? 'selected' : '' }}>12</option>
                            <option value="24" {{ request('per_page') == 24 ? 'selected' : '' }}>24</option>
                            <option value="36" {{ request('per_page') == 36 ? 'selected' : '' }}>36</option>
                            <option value="48" {{ request('per_page') == 48 ? 'selected' : '' }}>48</option>
                        </select>
                    </form>
                </div>
            </div>

            {{-- Products Grid --}}
            @if($products->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-12">
                @foreach($products as $product)
                <div class="group">
                    <div class="relative overflow-hidden rounded-lg mb-4 bg-gray-100">
                        <a href="{{ route('shop.show', $product->slug) }}">
                            <img
                                src="{{ asset($product->primaryImage?->path ?? 'images/placeholder.jpg') }}"
                                alt="{{ $product->name }}"
                                class="w-full h-80 object-cover transform group-hover:scale-105 transition-transform duration-300"
                            >
                        </a>

                        {{-- Badges --}}
                        <div class="absolute top-3 left-3 flex flex-col gap-2">
                            @if($product->is_new)
                            <span class="bg-gray-900 text-white text-xs font-semibold px-3 py-1 rounded">NOUVEAU</span>
                            @endif
                            @if($product->is_featured)
                            <span class="bg-yellow-400 text-gray-900 text-xs font-semibold px-3 py-1 rounded">EN VEDETTE</span>
                            @endif
                            @if($product->discount_percentage > 0)
                            <span class="bg-red-500 text-white text-xs font-semibold px-3 py-1 rounded">-{{ $product->discount_percentage }}%</span>
                            @endif
                        </div>

                        {{-- Quick Actions --}}
                        <div class="absolute top-3 right-3 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button
                                type="button"
                                class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-md hover:bg-gray-900 hover:text-white transition"
                                onclick="addToWishlist({{ $product->id }})"
                            >
                                <i class="fas fa-heart"></i>
                            </button>
                            <button
                                type="button"
                                class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-md hover:bg-gray-900 hover:text-white transition"
                                onclick="openQuickView('{{ $product->slug }}')"
                                title="Aperçu rapide"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>

                        {{-- Add to Cart Button --}}
                        <div class="absolute bottom-0 left-0 right-0 transform translate-y-full group-hover:translate-y-0 transition-transform">
                            <button
                                type="button"
                                class="w-full bg-gray-900 text-white py-3 font-semibold hover:bg-gray-800 transition"
                                onclick="addToCart({{ $product->id }})"
                            >
                                <i class="fas fa-shopping-bag mr-2"></i>Ajouter au panier
                            </button>
                        </div>
                    </div>

                    {{-- Product Info --}}
                    <div class="text-center">
                        {{-- Categories --}}
                        @if($product->categories->count() > 0)
                        <p class="text-xs text-gray-500 mb-1">
                            {{ $product->categories->pluck('name')->join(', ') }}
                        </p>
                        @endif

                        {{-- Name --}}
                        <h3 class="text-sm font-semibold mb-2">
                            <a href="{{ route('shop.show', $product->slug) }}" class="text-gray-900 hover:text-gray-600">
                                {{ $product->name }}
                            </a>
                        </h3>

                        {{-- Rating --}}
                        @if($product->reviews_count > 0)
                        <div class="flex items-center justify-center gap-1 mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star text-xs {{ $i <= round($product->reviews_avg_rating) ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                            @endfor
                            <span class="text-xs text-gray-600 ml-1">({{ $product->reviews_count }})</span>
                        </div>
                        @endif

                        {{-- Price --}}
                        <div class="flex items-center justify-center gap-2">
                            @if($product->compare_price && $product->compare_price > $product->price)
                            <span class="text-sm text-gray-400 line-through">{{ number_format($product->compare_price, 0) }} FCFA</span>
                            @endif
                            <span class="text-lg font-bold text-gray-900">{{ number_format($product->price, 0) }} FCFA</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="flex justify-center">
                {{ $products->links('vendor.pagination.custom') }}
            </div>

            @else
            {{-- No Products Found --}}
            <div class="text-center py-20">
                <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-2xl font-semibold text-gray-900 mb-2">Aucun produit trouvé</h3>
                <p class="text-gray-600 mb-6">Essayez d'ajuster vos filtres ou vos critères de recherche</p>
                <a href="{{ route('shop.index') }}" class="btn btn-primary">Effacer les filtres</a>
            </div>
            @endif

        </div>
    </div>
</div>

@endsection

@push('scripts')
@include('components.quick-view-modal')

<script>
function addToCart(productId) {
    fetch('{{ route("cart.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count
            updateCartCount(data.cart_count);
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Une erreur s\'est produite.', 'error');
    });
}

function addToWishlist(productId) {
    fetch('{{ route("wishlist.toggle") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update wishlist count
            updateWishlistCount(data.wishlist_count);
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Une erreur s\'est produite.', 'error');
    });
}

function quickView(productId) {
    // TODO: Implement quick view modal
    alert('Vue rapide: ' + productId);
}

function updateCartCount(count) {
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(el => {
        el.textContent = count;
        if (count > 0) {
            el.style.display = 'flex';
        } else {
            el.style.display = 'none';
        }
    });
}

function updateWishlistCount(count) {
    const wishlistCountElements = document.querySelectorAll('.wishlist-count');
    wishlistCountElements.forEach(el => {
        el.textContent = count;
        if (count > 0) {
            el.style.display = 'flex';
        } else {
            el.style.display = 'none';
        }
    });
}

function showNotification(message, type = 'success') {
    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';

    const notification = document.createElement('div');
    notification.className = `fixed bottom-8 right-8 ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg z-50 flex items-center gap-3`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>
@endpush
