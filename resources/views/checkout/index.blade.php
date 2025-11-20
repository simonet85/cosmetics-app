@extends('layouts.app')

@section('content')

{{-- Page Header --}}
<div class="bg-gray-50 py-12 mb-12">
    <div class="container-custom">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Paiement</h1>
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <a href="{{ route('cart.index') }}" class="text-gray-600 hover:text-gray-900">Panier</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">Paiement</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">

    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-6">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-6">
        <p class="font-semibold mb-2">Veuillez corriger les erreurs suivantes:</p>
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('checkout.process') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Checkout Form --}}
            <div class="lg:col-span-2 space-y-8">

                {{-- Shipping Information --}}
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">
                        <i class="fas fa-shipping-fast text-gray-600 mr-2"></i>Informations de livraison
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-2">Prénom *</label>
                            <input
                                type="text"
                                name="first_name"
                                value="{{ old('first_name') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 @error('first_name') border-red-500 @enderror"
                                required
                            >
                            @error('first_name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-2">Nom *</label>
                            <input
                                type="text"
                                name="last_name"
                                value="{{ old('last_name') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 @error('last_name') border-red-500 @enderror"
                                required
                            >
                            @error('last_name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-900 mb-2">Email *</label>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email', auth()->user()->email ?? '') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 @error('email') border-red-500 @enderror"
                                required
                            >
                            @error('email')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-900 mb-2">Téléphone *</label>
                            <input
                                type="tel"
                                name="phone"
                                value="{{ old('phone') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 @error('phone') border-red-500 @enderror"
                                required
                            >
                            @error('phone')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-2">Ville *</label>
                            <input
                                type="text"
                                name="city"
                                value="{{ old('city') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 @error('city') border-red-500 @enderror"
                                required
                            >
                            @error('city')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-2">Quartier *</label>
                            <input
                                type="text"
                                name="quartier"
                                value="{{ old('quartier') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 @error('quartier') border-red-500 @enderror"
                                required
                            >
                            @error('quartier')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Billing Address --}}
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">
                            <i class="fas fa-receipt text-gray-600 mr-2"></i>Adresse de facturation
                        </h2>
                        <label class="flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                name="billing_same_as_shipping"
                                value="1"
                                class="w-4 h-4 text-gray-900 focus:ring-gray-900 rounded"
                                id="billing-same"
                                {{ old('billing_same_as_shipping', true) ? 'checked' : '' }}
                            >
                            <span class="ml-2 text-sm text-gray-700">Identique à l'adresse de livraison</span>
                        </label>
                    </div>

                    <div id="billing-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4" style="display: none;">
                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-2">Prénom</label>
                            <input
                                type="text"
                                name="billing_first_name"
                                value="{{ old('billing_first_name') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-2">Nom</label>
                            <input
                                type="text"
                                name="billing_last_name"
                                value="{{ old('billing_last_name') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-2">Ville</label>
                            <input
                                type="text"
                                name="billing_city"
                                value="{{ old('billing_city') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-2">Quartier</label>
                            <input
                                type="text"
                                name="billing_quartier"
                                value="{{ old('billing_quartier') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900"
                            >
                        </div>
                    </div>
                </div>

                {{-- Payment Method --}}
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">
                        <i class="fas fa-credit-card text-gray-600 mr-2"></i>Méthode de paiement
                    </h2>

                    <div class="space-y-4">
                        @if($enabledPaymentMethods['credit_card'])
                        <label class="flex items-start p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-gray-900 transition">
                            <input
                                type="radio"
                                name="payment_method"
                                value="credit_card"
                                class="w-5 h-5 text-gray-900 focus:ring-gray-900 mt-1"
                                {{ old('payment_method', 'credit_card') == 'credit_card' ? 'checked' : '' }}
                                required
                            >
                            <div class="ml-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-900">Carte de crédit</span>
                                    <div class="flex gap-2">
                                        <i class="fab fa-cc-visa text-2xl text-blue-600"></i>
                                        <i class="fab fa-cc-mastercard text-2xl text-red-600"></i>
                                        <i class="fab fa-cc-amex text-2xl text-blue-400"></i>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">Paiement sécurisé par carte bancaire</p>
                            </div>
                        </label>
                        @endif

                        @if($enabledPaymentMethods['paypal'])
                        <label class="flex items-start p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-gray-900 transition">
                            <input
                                type="radio"
                                name="payment_method"
                                value="paypal"
                                class="w-5 h-5 text-gray-900 focus:ring-gray-900 mt-1"
                                {{ old('payment_method') == 'paypal' ? 'checked' : '' }}
                            >
                            <div class="ml-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-900">PayPal</span>
                                    <i class="fab fa-paypal text-2xl text-blue-600"></i>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">Paiement via votre compte PayPal</p>
                            </div>
                        </label>
                        @endif

                        @if($enabledPaymentMethods['bank_transfer'])
                        <label class="flex items-start p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-gray-900 transition">
                            <input
                                type="radio"
                                name="payment_method"
                                value="bank_transfer"
                                class="w-5 h-5 text-gray-900 focus:ring-gray-900 mt-1"
                                {{ old('payment_method') == 'bank_transfer' ? 'checked' : '' }}
                            >
                            <div class="ml-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-900">Virement bancaire</span>
                                    <i class="fas fa-university text-2xl text-gray-600"></i>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">Paiement par virement bancaire (traitement sous 2-3 jours)</p>
                            </div>
                        </label>
                        @endif

                        @if($enabledPaymentMethods['cash_on_delivery'])
                        <label class="flex items-start p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-gray-900 transition">
                            <input
                                type="radio"
                                name="payment_method"
                                value="cash_on_delivery"
                                class="w-5 h-5 text-gray-900 focus:ring-gray-900 mt-1"
                                {{ old('payment_method') == 'cash_on_delivery' ? 'checked' : '' }}
                            >
                            <div class="ml-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-900">Paiement à la livraison</span>
                                    <i class="fas fa-truck text-2xl text-green-600"></i>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">Payez en espèces lors de la réception de votre commande</p>
                            </div>
                        </label>
                        @endif

                        @if($enabledPaymentMethods['moneyfusion'])
                        <label class="flex items-start p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-gray-900 transition">
                            <input
                                type="radio"
                                name="payment_method"
                                value="moneyfusion"
                                class="w-5 h-5 text-gray-900 focus:ring-gray-900 mt-1"
                                {{ old('payment_method') == 'moneyfusion' ? 'checked' : '' }}
                            >
                            <div class="ml-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-gray-900">MoneyFusion</span>
                                    <img src="{{ asset('images/others/logo-money-fusion.svg') }}" alt="MoneyFusion" class="h-8" onerror="this.onerror=null; this.src='{{ asset('images/others/logo-fusion-money.webp') }}';">
                                </div>
                                <p class="text-sm text-gray-600 mt-1">Paiement mobile sécurisé (Orange Money, MTN Money, Wave, etc.)</p>
                                <div class="flex gap-2 mt-2">
                                    <span class="px-2 py-1 text-xs bg-orange-100 text-orange-800 rounded">Orange Money</span>
                                    <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">MTN Money</span>
                                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">Wave</span>
                                </div>
                            </div>
                        </label>
                        @endif
                    </div>
                </div>

                {{-- Additional Notes --}}
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-comment-alt text-gray-600 mr-2"></i>Notes de commande (optionnel)
                    </h2>
                    <textarea
                        name="notes"
                        rows="4"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900"
                        placeholder="Instructions spéciales pour la livraison..."
                    >{{ old('notes') }}</textarea>
                </div>

            </div>

            {{-- Order Summary --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm p-6 sticky top-24">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Résumé de la commande</h2>

                    {{-- Cart Items --}}
                    <div class="space-y-4 mb-6 pb-6 border-b border-gray-200">
                        @foreach($cartItems as $item)
                        <div class="flex items-start gap-3">
                            <img
                                src="{{ asset($item['product']->primaryImage?->path ?? 'images/placeholder.jpg') }}"
                                alt="{{ $item['product']->name }}"
                                class="w-16 h-16 object-cover rounded"
                            >
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold text-gray-900">{{ $item['product']->name }}</h4>
                                @if($item['variant'])
                                <p class="text-xs text-gray-600">{{ $item['variant']->variant_type }}: {{ $item['variant']->variant_value }}</p>
                                @endif
                                <p class="text-sm text-gray-600 mt-1">Qté: {{ $item['quantity'] }}</p>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">{{ number_format($item['subtotal'], 0) }} FCFA</span>
                        </div>
                        @endforeach
                    </div>

                    {{-- Totals --}}
                    <div class="space-y-3 mb-6 pb-6 border-b border-gray-200">
                        <div class="flex justify-between text-gray-700">
                            <span>Sous-total</span>
                            <span class="font-semibold">{{ number_format($subtotal, 0) }} FCFA</span>
                        </div>
                        <div class="flex justify-between text-gray-700">
                            <span>Taxes (15%)</span>
                            <span class="font-semibold">{{ number_format($tax, 0) }} FCFA</span>
                        </div>
                        <div class="flex justify-between text-gray-700">
                            <span>Livraison</span>
                            <span class="font-semibold">
                                @if($shipping == 0)
                                <span class="text-green-600">GRATUIT</span>
                                @else
                                {{ number_format($shipping, 0) }} FCFA
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-between text-lg font-bold text-gray-900 mb-6">
                        <span>Total</span>
                        <span>{{ number_format($total, 0) }} FCFA</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-full mb-4">
                        <i class="fas fa-lock mr-2"></i>Confirmer la commande
                    </button>

                    <p class="text-xs text-gray-600 text-center">
                        <i class="fas fa-shield-alt text-green-600 mr-1"></i>
                        Paiement 100% sécurisé
                    </p>
                </div>
            </div>

        </div>
    </form>

</div>

@endsection

@push('scripts')
<script>
// Toggle billing address fields
document.getElementById('billing-same').addEventListener('change', function() {
    const billingFields = document.getElementById('billing-fields');
    if (this.checked) {
        billingFields.style.display = 'none';
    } else {
        billingFields.style.display = 'grid';
    }
});

// Initialize on load
if (!document.getElementById('billing-same').checked) {
    document.getElementById('billing-fields').style.display = 'grid';
}
</script>
@endpush
