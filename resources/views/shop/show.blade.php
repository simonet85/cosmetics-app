@extends('layouts.app')

@section('content')

{{-- Breadcrumb --}}
<div class="bg-gray-50 py-4 mb-12">
    <div class="container-custom">
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <a href="{{ route('shop.index') }}" class="text-gray-600 hover:text-gray-900">Boutique</a>
            @if($product->categories->count() > 0)
            <span class="mx-2 text-gray-400">/</span>
            <a href="{{ route('shop.index', ['category' => $product->categories->first()->slug]) }}" class="text-gray-600 hover:text-gray-900">
                {{ $product->categories->first()->name }}
            </a>
            @endif
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">{{ $product->name }}</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">

    {{-- Product Main Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-20">

        {{-- Product Images --}}
        <div>
            {{-- Main Image --}}
            <div class="mb-4 bg-gray-100 rounded-lg overflow-hidden">
                <img
                    id="main-product-image"
                    src="{{ asset($product->primaryImage?->path ?? 'images/placeholder.jpg') }}"
                    alt="{{ $product->name }}"
                    class="w-full h-[600px] object-cover"
                >
            </div>

            {{-- Thumbnail Images --}}
            @if($product->images->count() > 1)
            <div class="grid grid-cols-4 gap-4">
                @foreach($product->images as $image)
                <div class="bg-gray-100 rounded-lg overflow-hidden cursor-pointer hover:opacity-75 transition" onclick="changeMainImage('{{ asset($image->path) }}')">
                    <img
                        src="{{ asset($image->path) }}"
                        alt="{{ $product->name }}"
                        class="w-full h-32 object-cover"
                    >
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Product Info --}}
        <div>
            {{-- Badges --}}
            <div class="flex gap-2 mb-4">
                @if($product->is_new)
                <span class="bg-gray-900 text-white text-xs font-semibold px-3 py-1 rounded">NOUVEAU</span>
                @endif
                @if($product->is_featured)
                <span class="bg-yellow-400 text-gray-900 text-xs font-semibold px-3 py-1 rounded">EN VEDETTE</span>
                @endif
                @if($product->is_best_seller)
                <span class="bg-blue-500 text-white text-xs font-semibold px-3 py-1 rounded">MEILLEURES VENTES</span>
                @endif
                @if($product->discount_percentage > 0)
                <span class="bg-red-500 text-white text-xs font-semibold px-3 py-1 rounded">-{{ $product->discount_percentage }}%</span>
                @endif
            </div>

            {{-- Product Name --}}
            <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ $product->name }}</h1>

            {{-- Rating & Reviews --}}
            @if($product->reviews_count > 0)
            <div class="flex items-center gap-3 mb-6">
                <div class="flex items-center gap-1">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fas fa-star {{ $i <= round($product->reviews_avg_rating) ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                    @endfor
                </div>
                <span class="text-gray-600">{{ number_format($product->reviews_avg_rating, 1) }}</span>
                <span class="text-gray-400">|</span>
                <a href="#reviews" class="text-gray-600 hover:text-gray-900">{{ $product->reviews_count }} {{ $product->reviews_count == 1 ? 'Avis' : 'Avis' }}</a>
            </div>
            @endif

            {{-- Price --}}
            <div class="flex items-center gap-4 mb-6">
                <span class="text-4xl font-bold text-gray-900">{{ number_format($product->price, 0) }} FCFA</span>
                @if($product->compare_price && $product->compare_price > $product->price)
                <span class="text-2xl text-gray-400 line-through">{{ number_format($product->compare_price, 0) }} FCFA</span>
                @endif
            </div>

            {{-- Short Description --}}
            <p class="text-gray-700 text-lg mb-8 leading-relaxed">
                {{ $product->short_description }}
            </p>

            {{-- Product Variants --}}
            @if($product->variants->count() > 0)
            <div class="mb-8">
                @php
                    $variantTypes = $product->variants->groupBy('variant_type');
                @endphp

                @foreach($variantTypes as $type => $variants)
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-900 mb-3">{{ ucfirst($type) }}</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($variants as $variant)
                        <button
                            type="button"
                            class="px-4 py-2 border-2 border-gray-300 rounded-lg hover:border-gray-900 transition variant-btn"
                            data-variant-id="{{ $variant->id }}"
                            data-price="{{ $variant->price }}"
                            onclick="selectVariant(this)"
                        >
                            {{ $variant->variant_value }}
                            @if($variant->price != $product->price)
                            <span class="text-sm text-gray-600">(+{{ number_format($variant->price - $product->price, 0) }} FCFA)</span>
                            @endif
                        </button>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Quantity & Add to Cart --}}
            <div class="flex items-center gap-4 mb-8">
                <div class="flex items-center border-2 border-gray-300 rounded-lg">
                    <button type="button" class="px-4 py-3 hover:bg-gray-100 transition" onclick="decreaseQuantity()">
                        <i class="fas fa-minus"></i>
                    </button>
                    <input
                        type="number"
                        id="quantity"
                        value="1"
                        min="1"
                        max="{{ $product->stock }}"
                        class="w-20 text-center border-x-2 border-gray-300 py-3 focus:outline-none"
                        readonly
                    >
                    <button type="button" class="px-4 py-3 hover:bg-gray-100 transition" onclick="increaseQuantity()">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>

                <button
                    type="button"
                    class="flex-1 btn btn-primary py-3 text-lg"
                    onclick="addToCart()"
                >
                    <i class="fas fa-shopping-bag mr-2"></i>Ajouter au panier
                </button>

                <button
                    type="button"
                    class="w-14 h-14 border-2 border-gray-300 rounded-lg hover:border-gray-900 hover:bg-gray-900 hover:text-white transition flex items-center justify-center"
                    onclick="addToWishlist({{ $product->id }})"
                >
                    <i class="fas fa-heart text-xl"></i>
                </button>
            </div>

            {{-- Stock Status --}}
            <div class="mb-8">
                @if($product->stock > 0)
                <p class="text-green-600 font-semibold">
                    <i class="fas fa-check-circle mr-2"></i>En stock ({{ $product->stock }} disponibles)
                </p>
                @else
                <p class="text-red-600 font-semibold">
                    <i class="fas fa-times-circle mr-2"></i>Rupture de stock
                </p>
                @endif
            </div>

            {{-- Product Meta --}}
            <div class="border-t border-gray-200 pt-6">
                <div class="space-y-3">
                    <div class="flex items-start">
                        <span class="text-gray-600 w-32">SKU:</span>
                        <span class="text-gray-900 font-medium">{{ $product->sku }}</span>
                    </div>
                    @if($product->categories->count() > 0)
                    <div class="flex items-start">
                        <span class="text-gray-600 w-32">Catégories:</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach($product->categories as $category)
                            <a href="{{ route('shop.index', ['category' => $category->slug]) }}" class="text-gray-900 hover:text-gray-600">
                                {{ $category->name }}{{ !$loop->last ? ',' : '' }}
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @if($product->tags->count() > 0)
                    <div class="flex items-start">
                        <span class="text-gray-600 w-32">Étiquettes:</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach($product->tags as $tag)
                            <a href="{{ route('shop.index', ['tag' => $tag->slug]) }}" class="bg-gray-100 px-3 py-1 rounded-full text-sm text-gray-900 hover:bg-gray-200">
                                {{ $tag->name }}
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Share --}}
            <div class="border-t border-gray-200 mt-6 pt-6">
                <div class="flex items-center gap-4">
                    <span class="text-gray-600">Partager:</span>
                    <a href="#" class="text-gray-600 hover:text-gray-900 transition"><i class="fab fa-facebook text-xl"></i></a>
                    <a href="#" class="text-gray-600 hover:text-gray-900 transition"><i class="fab fa-twitter text-xl"></i></a>
                    <a href="#" class="text-gray-600 hover:text-gray-900 transition"><i class="fab fa-pinterest text-xl"></i></a>
                    <a href="#" class="text-gray-600 hover:text-gray-900 transition"><i class="fab fa-whatsapp text-xl"></i></a>
                </div>
            </div>
        </div>
    </div>

    {{-- Product Tabs --}}
    <div class="mb-20">
        <div class="border-b border-gray-200 mb-8">
            <nav class="flex gap-8">
                <button class="tab-btn active pb-4 border-b-2 border-gray-900 text-gray-900 font-semibold" data-tab="description">
                    Description
                </button>
                <button class="tab-btn pb-4 border-b-2 border-transparent text-gray-600 hover:text-gray-900 font-semibold" data-tab="reviews">
                    Avis ({{ $product->reviews_count }})
                </button>
                <button class="tab-btn pb-4 border-b-2 border-transparent text-gray-600 hover:text-gray-900 font-semibold" data-tab="shipping">
                    Livraison & Retours
                </button>
            </nav>
        </div>

        {{-- Description Tab --}}
        <div class="tab-content active" id="description">
            <div class="prose max-w-none">
                {!! nl2br(e($product->full_description)) !!}
            </div>
        </div>

        {{-- Reviews Tab --}}
        <div class="tab-content hidden" id="reviews">
            @if($product->reviews->count() > 0)
            <div class="space-y-6 mb-12">
                @foreach($product->reviews as $review)
                <div class="border-b border-gray-200 pb-6">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center">
                                <span class="text-gray-600 font-semibold">{{ substr($review->user->name ?? 'Anonyme', 0, 1) }}</span>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">{{ $review->user->name ?? 'Anonyme' }}</h4>
                                <div class="flex items-center gap-2">
                                    <div class="flex">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star text-xs {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                                        @endfor
                                    </div>
                                    <span class="text-sm text-gray-500">{{ $review->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-700">{{ $review->comment }}</p>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-600 text-center py-12">Aucun avis pour le moment. Soyez le premier à donner votre avis sur ce produit!</p>
            @endif

            {{-- Review Form --}}
            @auth
            <div class="bg-gray-50 rounded-lg p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Écrire un avis</h3>
                <form action="{{ route('products.reviews.store', $product->slug) }}" method="POST">
                    @csrf

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Votre note</label>
                        <div class="flex gap-2">
                            @for($i = 1; $i <= 5; $i++)
                            <button type="button" class="rating-star text-3xl text-gray-300 hover:text-yellow-400 transition" data-rating="{{ $i }}">
                                <i class="fas fa-star"></i>
                            </button>
                            @endfor
                        </div>
                        <input type="hidden" name="rating" id="rating-input" required>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Votre avis</label>
                        <textarea
                            name="comment"
                            rows="5"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900"
                            placeholder="Partagez votre expérience avec ce produit..."
                            required
                        ></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Soumettre l'avis</button>
                </form>
            </div>
            @else
            <p class="text-center text-gray-600">
                <a href="{{ route('login') }}" class="text-gray-900 font-semibold hover:underline">Connectez-vous</a> pour écrire un avis
            </p>
            @endauth
        </div>

        {{-- Shipping Tab --}}
        <div class="tab-content hidden" id="shipping">
            <div class="prose max-w-none">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Informations de livraison</h3>
                <p class="text-gray-700 mb-4">Nous offrons la livraison standard gratuite pour toutes les commandes de plus de 50 $. Les commandes sont généralement traitées dans un délai de 1 à 2 jours ouvrables.</p>
                <ul class="list-disc pl-6 space-y-2 text-gray-700 mb-6">
                    <li>Livraison standard: 5-7 jours ouvrables</li>
                    <li>Livraison express: 2-3 jours ouvrables</li>
                    <li>Livraison le lendemain: 1 jour ouvrable</li>
                </ul>

                <h3 class="text-xl font-bold text-gray-900 mb-4">Retours et échanges</h3>
                <p class="text-gray-700 mb-4">Nous acceptons les retours dans les 30 jours suivant l'achat. Les articles doivent être inutilisés et dans leur emballage d'origine.</p>
                <p class="text-gray-700">Pour plus d'informations, veuillez consulter notre page <a href="#" class="text-gray-900 font-semibold hover:underline">Politique de retour</a>.</p>
            </div>
        </div>
    </div>

    {{-- Related Products --}}
    @if($relatedProducts->count() > 0)
    <div>
        <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Vous aimerez aussi</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($relatedProducts as $relatedProduct)
            <div class="group">
                <div class="relative overflow-hidden rounded-lg mb-4 bg-gray-100">
                    <a href="{{ route('shop.show', $relatedProduct->slug) }}">
                        <img
                            src="{{ asset($relatedProduct->primaryImage?->path ?? 'images/placeholder.jpg') }}"
                            alt="{{ $relatedProduct->name }}"
                            class="w-full h-80 object-cover transform group-hover:scale-105 transition-transform duration-300"
                        >
                    </a>
                </div>
                <div class="text-center">
                    <h3 class="text-sm font-semibold mb-2">
                        <a href="{{ route('shop.show', $relatedProduct->slug) }}" class="text-gray-900 hover:text-gray-600">
                            {{ $relatedProduct->name }}
                        </a>
                    </h3>
                    @if($relatedProduct->reviews_count > 0)
                    <div class="flex items-center justify-center gap-1 mb-2">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star text-xs {{ $i <= round($relatedProduct->reviews_avg_rating) ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                        @endfor
                    </div>
                    @endif
                    <div class="flex items-center justify-center gap-2">
                        <span class="text-lg font-bold text-gray-900">{{ number_format($relatedProduct->price, 0) }} FCFA</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

@endsection

@push('scripts')
<script>
// Change main image
function changeMainImage(src) {
    document.getElementById('main-product-image').src = src;
}

// Quantity controls
function increaseQuantity() {
    const input = document.getElementById('quantity');
    const max = parseInt(input.max);
    const current = parseInt(input.value);
    if (current < max) {
        input.value = current + 1;
    }
}

function decreaseQuantity() {
    const input = document.getElementById('quantity');
    const current = parseInt(input.value);
    if (current > 1) {
        input.value = current - 1;
    }
}

// Variant selection
let selectedVariantId = null;
let selectedPrice = {{ $product->price }};

function selectVariant(btn) {
    // Remove active state from all variant buttons
    document.querySelectorAll('.variant-btn').forEach(b => {
        b.classList.remove('border-gray-900', 'bg-gray-900', 'text-white');
        b.classList.add('border-gray-300');
    });

    // Add active state to selected button
    btn.classList.remove('border-gray-300');
    btn.classList.add('border-gray-900', 'bg-gray-900', 'text-white');

    selectedVariantId = btn.dataset.variantId;
    selectedPrice = parseFloat(btn.dataset.price);
}

// Add to cart
function addToCart() {
    const quantity = document.getElementById('quantity').value;

    fetch('{{ route("cart.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            product_id: {{ $product->id }},
            quantity: parseInt(quantity),
            variant_id: selectedVariantId
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

// Add to wishlist
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

// Tabs
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tabId = this.dataset.tab;

        // Remove active state from all tabs
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active', 'border-gray-900', 'text-gray-900');
            b.classList.add('border-transparent', 'text-gray-600');
        });

        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
            content.classList.add('hidden');
        });

        // Add active state to selected tab
        this.classList.add('active', 'border-gray-900', 'text-gray-900');
        this.classList.remove('border-transparent', 'text-gray-600');

        document.getElementById(tabId).classList.remove('hidden');
        document.getElementById(tabId).classList.add('active');
    });
});

// Rating stars
document.querySelectorAll('.rating-star').forEach(star => {
    star.addEventListener('click', function() {
        const rating = this.dataset.rating;
        document.getElementById('rating-input').value = rating;

        // Update star colors
        document.querySelectorAll('.rating-star').forEach((s, index) => {
            if (index < rating) {
                s.classList.remove('text-gray-300');
                s.classList.add('text-yellow-400');
            } else {
                s.classList.remove('text-yellow-400');
                s.classList.add('text-gray-300');
            }
        });
    });
});
</script>
@endpush
