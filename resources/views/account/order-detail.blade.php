@extends('layouts.app')

@section('content')

{{-- Page Header --}}
<div class="bg-gray-50 py-12 mb-12">
    <div class="container-custom">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Détails de la commande</h1>
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <a href="{{ route('account.dashboard') }}" class="text-gray-600 hover:text-gray-900">Mon compte</a>
            <span class="mx-2 text-gray-400">/</span>
            <a href="{{ route('account.orders') }}" class="text-gray-600 hover:text-gray-900">Commandes</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">{{ $order->order_number }}</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">
    <div class="max-w-5xl mx-auto">
        {{-- Order Header --}}
        <div class="bg-white rounded-lg shadow-sm p-8 mb-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">{{ $order->order_number }}</h2>
                    <p class="text-gray-600 mt-2">
                        <i class="fas fa-calendar mr-2"></i>
                        Commandé le {{ $order->created_at->format('d/m/Y à H:i') }}
                    </p>
                </div>

                <div class="flex gap-3">
                    <span class="px-4 py-2 rounded-lg text-sm font-semibold
                        @if($order->status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($order->status === 'processing') bg-blue-100 text-blue-800
                        @elseif($order->status === 'completed') bg-green-100 text-green-800
                        @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                        @endif">
                        @if($order->status === 'pending') En attente
                        @elseif($order->status === 'processing') En cours
                        @elseif($order->status === 'completed') Complétée
                        @elseif($order->status === 'cancelled') Annulée
                        @else {{ ucfirst($order->status) }}
                        @endif
                    </span>
                    <span class="px-4 py-2 rounded-lg text-sm font-semibold
                        @if($order->payment_status === 'paid') bg-green-100 text-green-800
                        @elseif($order->payment_status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($order->payment_status === 'failed') bg-red-100 text-red-800
                        @endif">
                        @if($order->payment_status === 'paid') Payé
                        @elseif($order->payment_status === 'pending') Paiement en attente
                        @elseif($order->payment_status === 'failed') Paiement échoué
                        @else {{ ucfirst($order->payment_status) }}
                        @endif
                    </span>
                </div>
            </div>

            {{-- Order Progress --}}
            @if($order->status !== 'cancelled')
            <div class="relative">
                <div class="flex justify-between mb-2">
                    <span class="text-sm font-medium {{ $order->status === 'pending' || $order->status === 'processing' || $order->status === 'completed' ? 'text-[#5a7c6f]' : 'text-gray-400' }}">Confirmée</span>
                    <span class="text-sm font-medium {{ $order->status === 'processing' || $order->status === 'completed' ? 'text-[#5a7c6f]' : 'text-gray-400' }}">En traitement</span>
                    <span class="text-sm font-medium {{ $order->status === 'completed' ? 'text-[#5a7c6f]' : 'text-gray-400' }}">Livrée</span>
                </div>
                <div class="relative h-2 bg-gray-200 rounded-full">
                    <div class="absolute h-full bg-[#5a7c6f] rounded-full transition-all
                        @if($order->status === 'pending') w-1/3
                        @elseif($order->status === 'processing') w-2/3
                        @elseif($order->status === 'completed') w-full
                        @endif">
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Order Items --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Articles commandés</h3>

                    <div class="divide-y divide-gray-200">
                        @foreach($order->items as $item)
                        <div class="py-4 flex items-center gap-4">
                            @if($item->product && $item->product->primaryImage)
                            <img
                                src="{{ asset($item->product->primaryImage->path) }}"
                                alt="{{ $item->product_name }}"
                                class="w-20 h-20 object-cover rounded border border-gray-200"
                            >
                            @else
                            <div class="w-20 h-20 bg-gray-200 rounded border border-gray-200 flex items-center justify-center">
                                <i class="fas fa-image text-gray-400"></i>
                            </div>
                            @endif
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900">{{ $item->product_name }}</h4>
                                @if($item->variant)
                                <p class="text-sm text-gray-600 mt-1">{{ $item->variant->variant_type }}: {{ $item->variant->variant_value }}</p>
                                @endif
                                <p class="text-sm text-gray-600 mt-1">SKU: {{ $item->sku }}</p>
                                <p class="text-sm text-gray-600">Quantité: {{ $item->quantity }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600">{{ number_format($item->price, 0) }} FCFA × {{ $item->quantity }}</p>
                                <p class="font-bold text-gray-900">{{ number_format($item->total, 0) }} FCFA</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Order Summary & Info --}}
            <div class="space-y-6">
                {{-- Order Summary --}}
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Résumé</h3>

                    <div class="space-y-3 mb-4 pb-4 border-b border-gray-200">
                        <div class="flex justify-between text-gray-700">
                            <span>Sous-total</span>
                            <span class="font-semibold">{{ number_format($order->subtotal, 0) }} FCFA</span>
                        </div>
                        <div class="flex justify-between text-gray-700">
                            <span>Taxes</span>
                            <span class="font-semibold">{{ number_format($order->tax, 0) }} FCFA</span>
                        </div>
                        <div class="flex justify-between text-gray-700">
                            <span>Livraison</span>
                            <span class="font-semibold">
                                @if($order->shipping_cost == 0)
                                <span class="text-green-600">GRATUIT</span>
                                @else
                                {{ number_format($order->shipping_cost, 0) }} FCFA
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-between text-xl font-bold text-gray-900">
                        <span>Total</span>
                        <span>{{ number_format($order->total, 0) }} FCFA</span>
                    </div>
                </div>

                {{-- Shipping Address --}}
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-shipping-fast mr-2 text-[#5a7c6f]"></i>
                        Adresse de livraison
                    </h3>
                    @php
                        $shipping = json_decode($order->shipping_address, true);
                    @endphp
                    <div class="text-sm text-gray-700 space-y-1">
                        <p class="font-semibold text-gray-900">{{ $shipping['first_name'] }} {{ $shipping['last_name'] }}</p>
                        <p>{{ $shipping['address'] }}</p>
                        <p>{{ $shipping['city'] }}, {{ $shipping['state'] }} {{ $shipping['zip_code'] }}</p>
                        <p>{{ $shipping['country'] }}</p>
                        <p class="mt-3">
                            <i class="fas fa-phone mr-2"></i>
                            {{ $shipping['phone'] }}
                        </p>
                    </div>
                </div>

                {{-- Payment Info --}}
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-credit-card mr-2 text-[#5a7c6f]"></i>
                        Paiement
                    </h3>
                    <div class="text-sm text-gray-700 space-y-2">
                        <p>
                            <span class="font-semibold">Méthode:</span>
                            {{ getPaymentMethodLabel($order->payment_method) }}
                        </p>
                        <p>
                            <span class="font-semibold">Statut:</span>
                            <span class="
                                @if($order->payment_status === 'paid') text-green-600
                                @elseif($order->payment_status === 'pending') text-yellow-600
                                @else text-red-600
                                @endif">
                                @if($order->payment_status === 'paid') Payé
                                @elseif($order->payment_status === 'pending') En attente
                                @else Échoué
                                @endif
                            </span>
                        </p>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="space-y-3">
                    <a href="{{ route('account.orders') }}" class="btn btn-outline w-full">
                        <i class="fas fa-arrow-left mr-2"></i>Retour aux commandes
                    </a>

                    @if($order->status === 'pending')
                    <form action="{{ route('account.orders.cancel', $order->id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette commande?')">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-outline w-full text-red-600 border-red-600 hover:bg-red-50">
                            <i class="fas fa-times mr-2"></i>Annuler la commande
                        </button>
                    </form>
                    @endif

                    @if($order->status === 'completed')
                    <a href="{{ route('shop.index') }}" class="btn btn-primary w-full">
                        <i class="fas fa-redo mr-2"></i>Recommander
                    </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Order Notes --}}
        @if($order->notes)
        <div class="bg-white rounded-lg shadow-sm p-6 mt-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-comment-alt mr-2 text-[#5a7c6f]"></i>
                Notes de commande
            </h3>
            <p class="text-gray-700">{{ $order->notes }}</p>
        </div>
        @endif
    </div>
</div>

@endsection
