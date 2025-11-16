@extends('layouts.app')

@section('content')

{{-- Page Header --}}
<div class="bg-gray-50 py-12 mb-12">
    <div class="container-custom">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Panier</h1>
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">Panier</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">

    @if(count($cartItems) > 0)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Cart Items --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                {{-- Table Header --}}
                <div class="hidden md:grid grid-cols-12 gap-4 p-6 bg-gray-50 border-b border-gray-200 font-semibold text-gray-900">
                    <div class="col-span-6">Produit</div>
                    <div class="col-span-2 text-center">Prix</div>
                    <div class="col-span-2 text-center">Quantité</div>
                    <div class="col-span-2 text-right">Total</div>
                </div>

                {{-- Cart Items --}}
                <div class="divide-y divide-gray-200">
                    @foreach($cartItems as $item)
                    <div class="p-6 cart-item" data-cart-item-id="{{ $item['id'] }}">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">

                            {{-- Product Info --}}
                            <div class="col-span-1 md:col-span-6">
                                <div class="flex items-start gap-4">
                                    {{-- Image --}}
                                    <div class="w-24 h-24 flex-shrink-0">
                                        <img
                                            src="{{ asset($item['product']->primaryImage?->path ?? 'images/placeholder.jpg') }}"
                                            alt="{{ $item['product']->name }}"
                                            class="w-full h-full object-cover rounded-lg"
                                        >
                                    </div>

                                    {{-- Details --}}
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 mb-1">
                                            <a href="{{ route('shop.show', $item['product']->slug) }}" class="hover:text-gray-600">
                                                {{ $item['product']->name }}
                                            </a>
                                        </h3>
                                        <p class="text-sm text-gray-600 mb-2">{{ $item['product']->short_description }}</p>

                                        @if($item['variant'])
                                        <p class="text-sm text-gray-500">
                                            <span class="font-medium">Variante:</span> {{ $item['variant']->variant_type }}: {{ $item['variant']->variant_value }}
                                        </p>
                                        @endif

                                        {{-- Remove Button (Mobile) --}}
                                        <button
                                            type="button"
                                            class="md:hidden text-sm text-red-600 hover:text-red-800 mt-2"
                                            onclick="removeFromCart('{{ $item['id'] }}')"
                                        >
                                            <i class="fas fa-trash mr-1"></i>Supprimer
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Price --}}
                            <div class="col-span-1 md:col-span-2">
                                <div class="md:text-center">
                                    <span class="md:hidden font-medium text-gray-700">Prix: </span>
                                    <span class="text-gray-900 font-semibold">{{ number_format($item['price'], 0) }} FCFA</span>
                                </div>
                            </div>

                            {{-- Quantity --}}
                            <div class="col-span-1 md:col-span-2">
                                <div class="flex items-center justify-start md:justify-center gap-2">
                                    <span class="md:hidden font-medium text-gray-700">Quantité: </span>
                                    <div class="flex items-center border-2 border-gray-300 rounded-lg">
                                        <button
                                            type="button"
                                            class="px-3 py-2 hover:bg-gray-100 transition"
                                            onclick="updateQuantity('{{ $item['id'] }}', {{ $item['quantity'] - 1 }})"
                                        >
                                            <i class="fas fa-minus text-sm"></i>
                                        </button>
                                        <input
                                            type="number"
                                            value="{{ $item['quantity'] }}"
                                            min="1"
                                            max="{{ $item['product']->stock }}"
                                            class="w-16 text-center border-x-2 border-gray-300 py-2 focus:outline-none quantity-input"
                                            data-cart-item-id="{{ $item['id'] }}"
                                            readonly
                                        >
                                        <button
                                            type="button"
                                            class="px-3 py-2 hover:bg-gray-100 transition"
                                            onclick="updateQuantity('{{ $item['id'] }}', {{ $item['quantity'] + 1 }})"
                                        >
                                            <i class="fas fa-plus text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Subtotal --}}
                            <div class="col-span-1 md:col-span-2">
                                <div class="flex items-center justify-between md:justify-end gap-4">
                                    <span class="md:hidden font-medium text-gray-700">Total: </span>
                                    <span class="text-gray-900 font-bold item-subtotal">{{ number_format($item['subtotal'], 0) }} FCFA</span>

                                    {{-- Remove Button (Desktop) --}}
                                    <button
                                        type="button"
                                        class="hidden md:block text-gray-400 hover:text-red-600 transition ml-4"
                                        onclick="removeFromCart('{{ $item['id'] }}')"
                                    >
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row justify-between gap-4 mt-6">
                <a href="{{ route('shop.index') }}" class="btn btn-outline">
                    <i class="fas fa-arrow-left mr-2"></i>Continuer mes achats
                </a>
                <button type="button" class="btn btn-outline text-red-600 border-red-600 hover:bg-red-600 hover:text-white" onclick="clearCart()">
                    <i class="fas fa-trash mr-2"></i>Vider le panier
                </button>
            </div>
        </div>

        {{-- Order Summary --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm p-6 sticky top-24">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Résumé de la commande</h2>

                <div class="space-y-4 mb-6 pb-6 border-b border-gray-200">
                    <div class="flex justify-between text-gray-700">
                        <span>Sous-total</span>
                        <span class="font-semibold" id="cart-subtotal">{{ number_format($subtotal, 0) }} FCFA</span>
                    </div>
                    <div class="flex justify-between text-gray-700">
                        <span>Taxes (15%)</span>
                        <span class="font-semibold" id="cart-tax">{{ number_format($tax, 0) }} FCFA</span>
                    </div>
                    <div class="flex justify-between text-gray-700">
                        <span>Livraison</span>
                        <span class="font-semibold" id="cart-shipping">
                            @if($shipping == 0)
                            <span class="text-green-600">GRATUIT</span>
                            @else
                            {{ number_format($shipping, 0) }} FCFA
                            @endif
                        </span>
                    </div>

                    @if($subtotal < 50)
                    <p class="text-sm text-gray-600 bg-blue-50 p-3 rounded-lg">
                        <i class="fas fa-info-circle text-blue-600 mr-1"></i>
                        Ajoutez <span class="font-semibold">{{ number_format(50 - $subtotal, 0) }} FCFA</span> pour la livraison gratuite!
                    </p>
                    @endif
                </div>

                <div class="flex justify-between text-lg font-bold text-gray-900 mb-6">
                    <span>Total</span>
                    <span id="cart-total">{{ number_format($total, 0) }} FCFA</span>
                </div>

                <a href="{{ route('checkout.index') }}" class="btn btn-primary w-full mb-4">
                    <i class="fas fa-lock mr-2"></i>Procéder au paiement
                </a>

                <p class="text-xs text-gray-600 text-center">
                    <i class="fas fa-shield-alt text-green-600 mr-1"></i>
                    Paiement sécurisé SSL
                </p>
            </div>
        </div>

    </div>

    @else
    {{-- Empty Cart --}}
    <div class="text-center py-20">
        <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-6"></i>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Votre panier est vide</h2>
        <p class="text-gray-600 mb-8">Ajoutez des produits à votre panier pour continuer vos achats.</p>
        <a href="{{ route('shop.index') }}" class="btn btn-primary">
            <i class="fas fa-shopping-bag mr-2"></i>Découvrir nos produits
        </a>
    </div>
    @endif

</div>

@endsection

@push('scripts')
<script>
// Update quantity
function updateQuantity(cartItemId, quantity) {
    if (quantity < 1) return;

    fetch('{{ route("cart.update") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            cart_item_id: cartItemId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update quantity input
            document.querySelector(`input[data-cart-item-id="${cartItemId}"]`).value = quantity;

            // Update item subtotal
            document.querySelector(`[data-cart-item-id="${cartItemId}"] .item-subtotal`).textContent = data.item_subtotal + ' FCFA';

            // Update cart summary
            document.getElementById('cart-subtotal').textContent = data.subtotal + ' FCFA';
            document.getElementById('cart-tax').textContent = data.tax + ' FCFA';

            if (parseFloat(data.shipping) === 0) {
                document.getElementById('cart-shipping').innerHTML = '<span class="text-green-600">GRATUIT</span>';
            } else {
                document.getElementById('cart-shipping').textContent = data.shipping + ' FCFA';
            }

            document.getElementById('cart-total').textContent = data.total + ' FCFA';

            // Update cart count in header
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

// Remove from cart
function removeFromCart(cartItemId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet article du panier?')) return;

    fetch('{{ route("cart.remove") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            cart_item_id: cartItemId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove item from DOM
            document.querySelector(`[data-cart-item-id="${cartItemId}"]`).remove();

            // Update cart summary
            document.getElementById('cart-subtotal').textContent = data.subtotal + ' FCFA';
            document.getElementById('cart-tax').textContent = data.tax + ' FCFA';

            if (parseFloat(data.shipping) === 0) {
                document.getElementById('cart-shipping').innerHTML = '<span class="text-green-600">GRATUIT</span>';
            } else {
                document.getElementById('cart-shipping').textContent = data.shipping + ' FCFA';
            }

            document.getElementById('cart-total').textContent = data.total + ' FCFA';

            // Update cart count in header
            updateCartCount(data.cart_count);

            showNotification(data.message, 'success');

            // Reload if cart is empty
            if (data.cart_count === 0) {
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

// Clear cart
function clearCart() {
    if (!confirm('Êtes-vous sûr de vouloir vider votre panier?')) return;

    fetch('{{ route("cart.clear") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(0);
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

// Update cart count in header
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
