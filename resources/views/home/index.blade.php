@extends('layouts.app')

@section('title', 'Accueil - ' . config('app.name'))

@section('content')

{{-- Hero Banner Carousel Section --}}
<section class="relative mb-16">
    <div class="hero-slider">
        @forelse($heroBanners as $banner)
        <div class="relative">
            <div class="relative h-[600px] lg:h-[700px] overflow-hidden">
                <img src="{{ asset($banner->image_path) }}" alt="{{ $banner->title }}" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-r from-black/30 to-transparent"></div>
                <div class="absolute inset-0 container-custom flex items-center">
                    <div class="max-w-2xl text-white">
                        @if($banner->subtitle)
                        <p class="text-sm uppercase tracking-wider font-semibold mb-4 animate-fade-in">{{ $banner->subtitle }}</p>
                        @endif
                        <h2 class="text-5xl lg:text-7xl font-bold leading-tight mb-6 animate-fade-in">
                            {!! nl2br(e($banner->title)) !!}
                        </h2>
                        @if($banner->description)
                        <p class="text-lg lg:text-xl mb-8 max-w-md animate-fade-in">
                            {{ $banner->description }}
                        </p>
                        @endif
                        @if($banner->link_url)
                        <a href="{{ $banner->link_url }}" class="btn btn-white inline-block animate-fade-in">
                            {{ $banner->button_text ?? 'Acheter Maintenant' }}
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="relative">
            <div class="relative h-[600px] lg:h-[700px] overflow-hidden">
                <img src="{{ asset('images/banner/banner-01.jpg') }}" alt="Glowing Banner" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-r from-black/30 to-transparent"></div>
                <div class="absolute inset-0 container-custom flex items-center">
                    <div class="max-w-2xl text-white">
                        <p class="text-sm uppercase tracking-wider font-semibold mb-4">gift for your skin</p>
                        <h2 class="text-5xl lg:text-7xl font-bold leading-tight mb-6">
                            Be Your<br>Kind of Beauty
                        </h2>
                        <p class="text-lg lg:text-xl mb-8 max-w-md">
                            Made using clean, non-toxic ingredients, our products are designed for everyone.
                        </p>
                        <a href="{{ route('shop.index') }}" class="btn btn-white inline-block">Acheter Maintenant</a>
                    </div>
                </div>
            </div>
        </div>
        @endforelse
    </div>
</section>

{{-- Our Featured Products Section --}}
<section class="py-16 bg-white">
    <div class="container-custom">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">Nos Produits en Vedette</h2>
            <p class="text-lg text-gray-600">Obtenez la peau que vous voulez ressentir</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($featuredProducts->take(4) as $product)
            <div class="group">
                <div class="relative overflow-hidden rounded-lg mb-4 bg-gray-100">
                    @if($product->discount_percentage)
                    <span class="absolute top-3 left-3 bg-red-500 text-white text-xs font-semibold px-2 py-1 rounded z-10">-{{ $product->discount_percentage }}%</span>
                    @endif
                    @if($product->is_new)
                    <span class="absolute top-3 right-3 bg-green-500 text-white text-xs font-semibold px-2 py-1 rounded z-10">Nouveau</span>
                    @endif
                    <a href="{{ route('products.show', $product->slug) }}">
                        @if($product->primary_image)
                        <img src="{{ asset($product->primary_image->path) }}" alt="{{ $product->name }}" class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-300">
                        @else
                        <img src="{{ asset('images/products/product-placeholder.jpg') }}" alt="{{ $product->name }}" class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-300">
                        @endif
                    </a>
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                        <button onclick="openQuickView('{{ $product->slug }}')" class="bg-white text-gray-900 w-10 h-10 rounded-full flex items-center justify-center hover:bg-gray-100 transition" title="Aperçu rapide">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="addToWishlist({{ $product->id }})" class="bg-white text-gray-900 w-10 h-10 rounded-full flex items-center justify-center hover:bg-gray-100 transition" title="Ajouter aux favoris">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="font-semibold text-gray-900 mb-2 hover:text-gray-600 transition">
                        <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                    </h3>
                    <div class="flex items-center justify-center gap-1 mb-2">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= floor($product->reviews_avg_rating ?? 0))
                            <i class="fas fa-star text-yellow-400 text-sm"></i>
                            @else
                            <i class="far fa-star text-gray-300 text-sm"></i>
                            @endif
                        @endfor
                        <span class="text-gray-500 text-sm ml-1">({{ $product->reviews_count ?? 0 }})</span>
                    </div>
                    <div class="flex items-center justify-center gap-2">
                        @if($product->discount_price)
                        <span class="text-gray-400 line-through">{{ number_format($product->price, 0) }} FCFA</span>
                        <span class="text-red-600 font-bold">{{ number_format($product->discount_price, 0) }} FCFA</span>
                        @else
                        <span class="text-gray-900 font-bold">{{ number_format($product->price, 0) }} FCFA</span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Promotional Banners Section --}}
<section class="py-16 bg-gray-50">
    <div class="container-custom">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($banners->where('position', 'promo')->take(2) as $banner)
            <div class="relative overflow-hidden rounded-lg h-80 group">
                <img src="{{ asset($banner->image_path) }}" alt="{{ $banner->title }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                <div class="absolute bottom-0 left-0 right-0 p-8 text-white">
                    @if($banner->subtitle)
                    <p class="text-sm uppercase tracking-wider font-semibold mb-2">{{ $banner->subtitle }}</p>
                    @endif
                    <h3 class="text-3xl font-bold mb-4">{{ $banner->title }}</h3>
                    @if($banner->link_url)
                    <a href="{{ $banner->link_url }}" class="btn btn-white inline-block">{{ $banner->button_text ?? 'Explorer Plus' }}</a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Features Section --}}
<section class="py-16 bg-white">
    <div class="container-custom">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-truck text-2xl text-gray-900"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Livraison Gratuite</h3>
                <p class="text-gray-600">Livraison gratuite à partir de 300 FCFA</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-medal text-2xl text-gray-900"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Récompenses</h3>
                <p class="text-gray-600">Gagnez des points à chaque achat</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-headset text-2xl text-gray-900"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Support En Ligne</h3>
                <p class="text-gray-600">24 heures sur 24, 7 jours sur 7</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-credit-card text-2xl text-gray-900"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">Paiement Flexible</h3>
                <p class="text-gray-600">Payez avec plusieurs cartes de crédit</p>
            </div>
        </div>
    </div>
</section>

{{-- Testimonials Section --}}
@if($testimonials->count() > 0)
<section class="py-16 bg-gray-50">
    <div class="container-custom">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900">Vu dans</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($testimonials->take(3) as $testimonial)
            <div class="bg-white rounded-lg p-8 shadow-sm">
                <p class="text-lg italic text-gray-700 mb-6">"{{ $testimonial->content }}"</p>
                <h4 class="font-semibold text-gray-900">{{ $testimonial->author_name }}</h4>
                @if($testimonial->author_title)
                <p class="text-gray-600 text-sm">{{ $testimonial->author_title }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Customer Favorite Beauty Essentials Section --}}
<section class="py-16 bg-white">
    <div class="container-custom">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">Essentiels de Beauté Favoris des Clients</h2>
            <p class="text-lg text-gray-600">Nos produits les plus vendus basés sur les ventes</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($bestSellers->take(8) as $product)
            <div class="group">
                <div class="relative overflow-hidden rounded-lg mb-4 bg-gray-100">
                    @if($product->is_best_seller)
                    <span class="absolute top-3 left-3 bg-yellow-500 text-white text-xs font-semibold px-2 py-1 rounded z-10">Meilleures Ventes</span>
                    @endif
                    <a href="{{ route('products.show', $product->slug) }}">
                        @if($product->primary_image)
                        <img src="{{ asset($product->primary_image->path) }}" alt="{{ $product->name }}" class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-300">
                        @else
                        <img src="{{ asset('images/products/product-placeholder.jpg') }}" alt="{{ $product->name }}" class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-300">
                        @endif
                    </a>
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                        <button onclick="openQuickView('{{ $product->slug }}')" class="bg-white text-gray-900 w-10 h-10 rounded-full flex items-center justify-center hover:bg-gray-100 transition" title="Aperçu rapide">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="addToWishlist({{ $product->id }})" class="bg-white text-gray-900 w-10 h-10 rounded-full flex items-center justify-center hover:bg-gray-100 transition" title="Ajouter aux favoris">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="font-semibold text-gray-900 mb-2 hover:text-gray-600 transition">
                        <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                    </h3>
                    <div class="flex items-center justify-center gap-1 mb-2">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= floor($product->reviews_avg_rating ?? 0))
                            <i class="fas fa-star text-yellow-400 text-sm"></i>
                            @else
                            <i class="far fa-star text-gray-300 text-sm"></i>
                            @endif
                        @endfor
                        <span class="text-gray-500 text-sm ml-1">({{ $product->reviews_count ?? 0 }})</span>
                    </div>
                    <div class="flex items-center justify-center gap-2">
                        @if($product->discount_price)
                        <span class="text-gray-400 line-through">{{ number_format($product->price, 0) }} FCFA</span>
                        <span class="text-red-600 font-bold">{{ number_format($product->discount_price, 0) }} FCFA</span>
                        @else
                        <span class="text-gray-900 font-bold">{{ number_format($product->price, 0) }} FCFA</span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- More to Discover - Collections Section --}}
<section class="py-16 bg-gray-50">
    <div class="container-custom">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">Plus à Découvrir</h2>
            <p class="text-lg text-gray-600">Notre collection de soins de la peau est conçue pour restaurer l'éclat naturel de votre peau</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($categories->take(2) as $category)
            <div class="relative overflow-hidden rounded-lg h-96 group">
                @if($category->image)
                <img src="{{ asset($category->image) }}" alt="{{ $category->name }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                @else
                <img src="{{ asset('images/categories/category-placeholder.jpg') }}" alt="{{ $category->name }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                <div class="absolute bottom-0 left-0 right-0 p-8 text-white">
                    <h3 class="text-3xl font-bold mb-4">{{ $category->name }}</h3>
                    <a href="{{ route('shop.category', $category->slug) }}" class="btn btn-white inline-block">Acheter Maintenant</a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Newsletter Section --}}
<section class="py-16 bg-white">
    <div class="container-custom">
        <div class="max-w-4xl mx-auto text-center lg:text-left lg:flex lg:items-center lg:justify-between gap-8">
            <div class="mb-6 lg:mb-0">
                <h3 class="text-3xl font-bold text-gray-900 mb-4">Bons emails.</h3>
                <p class="text-lg text-gray-600">
                    Entrez votre email ci-dessous pour être le premier à connaître les nouvelles collections et lancements de produits.
                </p>
            </div>
            <div class="lg:w-96 flex-shrink-0">
                <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex flex-col sm:flex-row gap-2">
                    @csrf
                    <input type="email" name="email" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900" placeholder="Entrez votre adresse email" required>
                    <button type="submit" class="btn btn-primary whitespace-nowrap">S'abonner</button>
                </form>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
@include('components.quick-view-modal')

<script>
$(document).ready(function(){
    // Initialize hero slider
    $('.hero-slider').slick({
        autoplay: true,
        autoplaySpeed: 5000,
        dots: true,
        arrows: true,
        infinite: true,
        speed: 800,
        fade: true,
        cssEase: 'ease-in-out',
        prevArrow: '<button type="button" class="absolute left-4 top-1/2 -translate-y-1/2 z-10 bg-white/80 hover:bg-white w-12 h-12 rounded-full flex items-center justify-center transition"><i class="fas fa-chevron-left text-gray-900"></i></button>',
        nextArrow: '<button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 z-10 bg-white/80 hover:bg-white w-12 h-12 rounded-full flex items-center justify-center transition"><i class="fas fa-chevron-right text-gray-900"></i></button>',
        responsive: [
            {
                breakpoint: 768,
                settings: {
                    arrows: false
                }
            }
        ]
    });
});

// Add to Wishlist function
function addToWishlist(productId) {
    fetch('/wishlist/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ product_id: productId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert('Produit ajouté aux favoris!');

            // Update wishlist counter
            const wishlistCount = document.getElementById('wishlist-count');
            if (wishlistCount) {
                wishlistCount.textContent = data.wishlist_count;
                wishlistCount.classList.remove('hidden');
            }
        } else {
            alert(data.message || 'Erreur lors de l\'ajout aux favoris');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de l\'ajout aux favoris');
    });
}
</script>
@endpush
