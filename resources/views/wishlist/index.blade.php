@extends('layouts.app')

@section('content')

{{-- Page Header --}}
<div class="bg-gray-50 py-12 mb-12">
    <div class="container-custom">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Liste de souhaits</h1>
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">Liste de souhaits</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">

    @if(count($wishlistItems) > 0)
    <div class="mb-6 flex justify-between items-center">
        <p class="text-gray-600">
            <span class="font-semibold text-gray-900">{{ count($wishlistItems) }}</span>
            {{ count($wishlistItems) == 1 ? 'produit' : 'produits' }} dans votre liste de souhaits
        </p>
        <button type="button" class="btn btn-outline text-red-600 border-red-600 hover:bg-red-600 hover:text-white" onclick="clearWishlist()">
            <i class="fas fa-trash mr-2"></i>Vider la liste
        </button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach($wishlistItems as $product)
        <div class="group wishlist-item" data-product-id="{{ $product->id }}">
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
                    <span class="bg-gray-900 text-white text-xs font-semibold px-3 py-1 rounded">NEW</span>
                    @endif
                    @if($product->is_featured)
                    <span class="bg-yellow-400 text-gray-900 text-xs font-semibold px-3 py-1 rounded">VEDETTE</span>
                    @endif
                    @if($product->discount_percentage > 0)
                    <span class="bg-red-500 text-white text-xs font-semibold px-3 py-1 rounded">-{{ $product->discount_percentage }}%</span>
                    @endif
                </div>

                {{-- Remove from Wishlist --}}
                <button
                    type="button"
                    class="absolute top-3 right-3 w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-md hover:bg-red-500 hover:text-white transition text-red-500"
                    onclick="removeFromWishlist({{ $product->id }})"
                    title="Retirer de la liste"
                >
                    <i class="fas fa-heart"></i>
                </button>

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

                {{-- Stock Status --}}
                @if($product->stock > 0)
                <p class="text-xs text-green-600 mt-2">
                    <i class="fas fa-check-circle mr-1"></i>En stock
                </p>
                @else
                <p class="text-xs text-red-600 mt-2">
                    <i class="fas fa-times-circle mr-1"></i>Rupture de stock
                </p>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Actions --}}
    <div class="flex flex-col sm:flex-row justify-between gap-4 mt-12">
        <a href="{{ route('shop.index') }}" class="btn btn-outline">
            <i class="fas fa-arrow-left mr-2"></i>Continuer mes achats
        </a>
        <a href="{{ route('cart.index') }}" class="btn btn-primary">
            <i class="fas fa-shopping-cart mr-2"></i>Voir mon panier
        </a>
    </div>

    @else
    {{-- Empty Wishlist --}}
    <div class="text-center py-20">
        <i class="fas fa-heart text-6xl text-gray-300 mb-6"></i>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Votre liste de souhaits est vide</h2>
        <p class="text-gray-600 mb-8">Ajoutez vos produits préférés à votre liste de souhaits pour les retrouver facilement.</p>
        <a href="{{ route('shop.index') }}" class="btn btn-primary">
            <i class="fas fa-shopping-bag mr-2"></i>Découvrir nos produits
        </a>
    </div>
    @endif

</div>

@endsection

@push('scripts')
<script>
// Add to cart from wishlist
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

// Remove from wishlist
function removeFromWishlist(productId) {
    fetch('{{ route("wishlist.remove") }}', {
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
            // Remove item from DOM
            document.querySelector(`.wishlist-item[data-product-id="${productId}"]`).remove();

            // Update wishlist count
            updateWishlistCount(data.wishlist_count);

            showNotification(data.message, 'success');

            // Reload if wishlist is empty
            if (data.wishlist_count === 0) {
                setTimeout(() => location.reload(), 1000);
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Une erreur s\'est produite.', 'error');
    });
}

// Clear wishlist
function clearWishlist() {
    if (!confirm('Êtes-vous sûr de vouloir vider votre liste de souhaits?')) return;

    fetch('{{ route("wishlist.clear") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateWishlistCount(0);
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Une erreur s\'est produite.', 'error');
    });
}

// Update cart count
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

// Update wishlist count
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

// Show notification
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
