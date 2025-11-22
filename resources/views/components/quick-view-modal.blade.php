<!-- Quick View Modal -->
<div id="quickViewModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 items-center justify-center p-4" onclick="closeQuickViewIfOutside(event)">
    <div class="bg-white rounded-lg max-w-5xl w-full max-h-[90vh] overflow-y-auto relative" onclick="event.stopPropagation()">
        <!-- Close Button -->
        <button onclick="closeQuickView()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-900 z-10 w-8 h-8 flex items-center justify-center bg-white rounded-full shadow-md">
            <i class="fas fa-times text-xl"></i>
        </button>

        <!-- Loading State -->
        <div id="quickViewLoading" class="p-12 text-center">
            <i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i>
            <p class="text-gray-600 mt-4">Chargement...</p>
        </div>

        <!-- Content -->
        <div id="quickViewContent" class="hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-8">
                <!-- Left Column - Images -->
                <div>
                    <!-- Icon Actions -->
                    <div class="flex gap-2 mb-4">
                        <button onclick="addToWishlistFromQuickView()" class="w-10 h-10 border border-gray-300 rounded-full flex items-center justify-center hover:bg-gray-100 transition">
                            <i class="far fa-heart"></i>
                        </button>
                        <button class="w-10 h-10 border border-gray-300 rounded-full flex items-center justify-center hover:bg-gray-100 transition">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                    </div>

                    <!-- Main Image -->
                    <div class="mb-4 bg-gray-50 rounded-lg overflow-hidden">
                        <img id="qv-main-image" src="" alt="" class="w-full h-96 object-contain">
                    </div>

                    <!-- Thumbnail Images -->
                    <div id="qv-thumbnails" class="grid grid-cols-4 gap-2">
                        <!-- Thumbnails will be inserted here -->
                    </div>
                </div>

                <!-- Right Column - Product Info -->
                <div>
                    <!-- Price -->
                    <div class="mb-4">
                        <span id="qv-price" class="text-3xl font-bold text-gray-900"></span>
                    </div>

                    <!-- Product Name -->
                    <h2 id="qv-name" class="text-3xl font-bold text-gray-900 mb-4"></h2>

                    <!-- Rating & Reviews -->
                    <div class="flex items-center gap-2 mb-4">
                        <div id="qv-rating" class="flex items-center gap-1">
                            <!-- Stars will be inserted here -->
                        </div>
                        <span id="qv-reviews-count" class="text-gray-600"></span>
                    </div>

                    <!-- Description -->
                    <p id="qv-description" class="text-gray-700 mb-4 leading-relaxed"></p>

                    <!-- Viewers -->
                    <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
                        <i class="far fa-eye"></i>
                        <span><span id="qv-viewers">17</span> personnes consultent ce produit maintenant</span>
                    </div>

                    <!-- Stock Status -->
                    <div id="qv-stock" class="flex items-center gap-2 text-sm mb-6">
                        <!-- Stock info will be inserted here -->
                    </div>

                    <!-- Quantity & Add to Cart -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Quantité:</label>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center border border-gray-300 rounded">
                                <button type="button" onclick="decreaseQVQuantity()" class="px-4 py-2 hover:bg-gray-100 transition">
                                    <i class="fas fa-minus text-sm"></i>
                                </button>
                                <input type="number" id="qv-quantity" value="1" min="1" class="w-16 text-center border-x border-gray-300 py-2 focus:outline-none" readonly>
                                <button type="button" onclick="increaseQVQuantity()" class="px-4 py-2 hover:bg-gray-100 transition">
                                    <i class="fas fa-plus text-sm"></i>
                                </button>
                            </div>
                            <button onclick="addToCartFromQuickView()" class="flex-1 bg-black text-white px-8 py-3 rounded hover:bg-gray-800 transition font-semibold">
                                Ajouter au Panier
                            </button>
                        </div>
                    </div>

                    <!-- Delivery Info -->
                    <div class="border-t border-gray-200 pt-4 mb-4">
                        <div class="flex items-start gap-2 text-sm text-gray-600 mb-2">
                            <i class="fas fa-truck mt-1"></i>
                            <span>Récupérer entre <strong>Fév 3 - Fév 14 2021</strong></span>
                        </div>
                        <div class="flex items-start gap-2 text-sm text-gray-600">
                            <i class="fas fa-undo mt-1"></i>
                            <span>Livraison & Retours Gratuits: Sur toutes les commandes de plus de $200</span>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="flex items-center justify-center gap-3 mb-2">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/Visa.svg" alt="Visa" class="h-8">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard" class="h-8">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/American_Express_logo_%282018%29.svg" alt="Amex" class="h-8">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/4/40/JCB_logo.svg" alt="JCB" class="h-8">
                    </div>
                    <p class="text-sm text-gray-500 text-center mb-6">Paiement sécurisé garanti</p>

                    <!-- SKU & Categories -->
                    <div class="border-t border-gray-200 pt-4 space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600">Sku:</span>
                            <span id="qv-sku" class="text-gray-900 font-semibold"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600">Catégories:</span>
                            <div id="qv-categories" class="flex flex-wrap gap-1"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentProduct = null;

// Open Quick View
function openQuickView(productSlug) {
    const modal = document.getElementById('quickViewModal');
    const loading = document.getElementById('quickViewLoading');
    const content = document.getElementById('quickViewContent');

    // Show modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    // Show loading, hide content
    loading.classList.remove('hidden');
    content.classList.add('hidden');

    // Fetch product data
    fetch(`/products/${productSlug}/quick-view`)
        .then(response => response.json())
        .then(data => {
            currentProduct = data;
            renderQuickView(data);
            loading.classList.add('hidden');
            content.classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error:', error);
            loading.innerHTML = '<div class="text-center py-12 text-red-600">Erreur lors du chargement du produit</div>';
        });
}

// Render Quick View Content
function renderQuickView(product) {
    // Main Image
    document.getElementById('qv-main-image').src = product.primary_image;
    document.getElementById('qv-main-image').alt = product.name;

    // Thumbnails
    const thumbnailsContainer = document.getElementById('qv-thumbnails');
    thumbnailsContainer.innerHTML = '';
    product.images.forEach(image => {
        const thumb = document.createElement('div');
        thumb.className = 'border-2 border-gray-200 rounded cursor-pointer hover:border-gray-400 transition overflow-hidden';
        thumb.innerHTML = `<img src="${image.path}" alt="${product.name}" class="w-full h-20 object-contain" onclick="changeQVMainImage('${image.path}')">`;
        thumbnailsContainer.appendChild(thumb);
    });

    // Price
    document.getElementById('qv-price').textContent = `${new Intl.NumberFormat('fr-FR').format(product.price)} FCFA`;

    // Product Name
    document.getElementById('qv-name').textContent = product.name;

    // Rating
    const ratingContainer = document.getElementById('qv-rating');
    ratingContainer.innerHTML = '';
    for (let i = 1; i <= 5; i++) {
        const star = document.createElement('i');
        star.className = i <= Math.round(product.reviews_avg_rating || 0) ? 'fas fa-star text-yellow-400 text-sm' : 'far fa-star text-gray-300 text-sm';
        ratingContainer.appendChild(star);
    }
    const reviewsCount = product.reviews_count || 0;
    document.getElementById('qv-reviews-count').textContent = `${reviewsCount} Lire ${reviewsCount} avis`;

    // Description
    document.getElementById('qv-description').textContent = product.short_description;

    // Stock
    const stockContainer = document.getElementById('qv-stock');
    if (product.stock > 0) {
        stockContainer.innerHTML = `<i class="fas fa-check-circle text-green-600"></i><span class="text-green-600 font-semibold">Seulement ${product.stock} en stock</span>`;
    } else {
        stockContainer.innerHTML = `<i class="fas fa-times-circle text-red-600"></i><span class="text-red-600 font-semibold">Rupture de stock</span>`;
    }

    // Set max quantity
    document.getElementById('qv-quantity').max = product.stock;

    // SKU
    document.getElementById('qv-sku').textContent = product.sku;

    // Categories
    const categoriesContainer = document.getElementById('qv-categories');
    categoriesContainer.innerHTML = '';
    product.categories.forEach((category, index) => {
        const link = document.createElement('a');
        link.href = `/shop?category=${category.slug}`;
        link.textContent = category.name;
        link.className = 'text-gray-900 hover:underline';
        categoriesContainer.appendChild(link);
        if (index < product.categories.length - 1) {
            categoriesContainer.appendChild(document.createTextNode(', '));
        }
    });
}

// Close Quick View
function closeQuickView() {
    const modal = document.getElementById('quickViewModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    currentProduct = null;
}

// Close if clicked outside
function closeQuickViewIfOutside(event) {
    if (event.target.id === 'quickViewModal') {
        closeQuickView();
    }
}

// Change Main Image
function changeQVMainImage(imagePath) {
    document.getElementById('qv-main-image').src = imagePath;
}

// Quantity Controls
function decreaseQVQuantity() {
    const input = document.getElementById('qv-quantity');
    if (input.value > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

function increaseQVQuantity() {
    const input = document.getElementById('qv-quantity');
    const max = parseInt(input.max);
    if (parseInt(input.value) < max) {
        input.value = parseInt(input.value) + 1;
    }
}

// Add to Cart from Quick View
function addToCartFromQuickView() {
    if (!currentProduct) return;

    const quantity = parseInt(document.getElementById('qv-quantity').value);

    fetch('/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            product_id: currentProduct.id,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Produit ajouté au panier!');
            // Update cart counter
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                cartCount.textContent = data.cart_count;
                cartCount.classList.remove('hidden');
            }
            closeQuickView();
        } else {
            alert(data.message || 'Erreur lors de l\'ajout au panier');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de l\'ajout au panier');
    });
}

// Add to Wishlist from Quick View
function addToWishlistFromQuickView() {
    if (!currentProduct) return;
    addToWishlist(currentProduct.id);
}
</script>
