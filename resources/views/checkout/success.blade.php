@extends('layouts.app')

@section('content')

<div class="container-custom py-20">

    {{-- Success Message --}}
    <div class="text-center mb-12">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-6">
            <i class="fas fa-check text-4xl text-green-600"></i>
        </div>
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Commande confirmée!</h1>
        <p class="text-xl text-gray-600 mb-2">Merci pour votre commande</p>
        <p class="text-gray-600">Numéro de commande: <span class="font-semibold text-gray-900">{{ $order->order_number }}</span></p>
    </div>

    {{-- Order Details --}}
    <div class="max-w-4xl mx-auto">

        {{-- Order Info Card --}}
        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 pb-8 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de commande</h3>
                    <div class="space-y-2 text-sm">
                        <p class="text-gray-600">
                            <span class="font-medium text-gray-900">Date:</span>
                            {{ $order->created_at->format('d/m/Y à H:i') }}
                        </p>
                        <p class="text-gray-600">
                            <span class="font-medium text-gray-900">Email:</span>
                            {{ $order->customer_email }}
                        </p>
                        <p class="text-gray-600">
                            <span class="font-medium text-gray-900">Téléphone:</span>
                            {{ $order->customer_phone }}
                        </p>
                        <p class="text-gray-600">
                            <span class="font-medium text-gray-900">Statut:</span>
                            <span class="inline-block px-2 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-800">
                                {{ ucfirst($order->status) }}
                            </span>
                        </p>
                        <p class="text-gray-600">
                            <span class="font-medium text-gray-900">Paiement:</span>
                            <span class="inline-block px-2 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-800">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        </p>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Adresse de livraison</h3>
                    @php
                        $shipping = json_decode($order->shipping_address, true);
                    @endphp
                    <div class="text-sm text-gray-600">
                        <p class="font-medium text-gray-900">{{ $shipping['first_name'] }} {{ $shipping['last_name'] }}</p>
                        <p>{{ $shipping['address'] }}</p>
                        <p>{{ $shipping['city'] }}, {{ $shipping['state'] }} {{ $shipping['zip_code'] }}</p>
                        <p>{{ $shipping['country'] }}</p>
                        <p class="mt-2">{{ $shipping['phone'] }}</p>
                    </div>
                </div>
            </div>

            {{-- Order Items --}}
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Articles commandés</h3>
                <div class="divide-y divide-gray-200">
                    @foreach($order->items as $item)
                    <div class="py-4 flex items-center gap-4">
                        <img
                            src="{{ asset($item->product->primaryImage?->path ?? 'images/placeholder.jpg') }}"
                            alt="{{ $item->product->name }}"
                            class="w-20 h-20 object-cover rounded"
                        >
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900">{{ $item->product->name }}</h4>
                            @if($item->variant)
                            <p class="text-sm text-gray-600">{{ $item->variant->variant_type }}: {{ $item->variant->variant_value }}</p>
                            @endif
                            <p class="text-sm text-gray-600 mt-1">Quantité: {{ $item->quantity }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">{{ number_format($item->price, 0) }} FCFA × {{ $item->quantity }}</p>
                            <p class="font-semibold text-gray-900">{{ number_format($item->subtotal, 0) }} FCFA</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Order Summary --}}
            <div class="bg-gray-50 rounded-lg p-6">
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
        </div>

        {{-- What's Next --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>Que se passe-t-il maintenant?
            </h3>
            <ul class="space-y-2 text-sm text-gray-700">
                <li class="flex items-start">
                    <i class="fas fa-check text-green-600 mr-2 mt-1"></i>
                    <span>Un email de confirmation a été envoyé à <strong>{{ $order->customer_email }}</strong></span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check text-green-600 mr-2 mt-1"></i>
                    <span>Votre commande sera traitée dans les 24 heures</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check text-green-600 mr-2 mt-1"></i>
                    <span>Vous recevrez un email avec les informations de suivi dès que votre commande sera expédiée</span>
                </li>
                @if($order->payment_method == 'bank_transfer')
                <li class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-2 mt-1"></i>
                    <span><strong>Important:</strong> Veuillez effectuer le virement bancaire dans les 3 jours. Les détails ont été envoyés par email.</span>
                </li>
                @endif
                @if($order->payment_method == 'cash_on_delivery')
                <li class="flex items-start">
                    <i class="fas fa-money-bill-wave text-green-600 mr-2 mt-1"></i>
                    <span><strong>Paiement à la livraison:</strong> Préparez le montant exact en espèces. Le livreur vous remettra votre commande après paiement.</span>
                </li>
                @endif
            </ul>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('home') }}" class="btn btn-outline">
                <i class="fas fa-home mr-2"></i>Retour à l'accueil
            </a>
            <a href="{{ route('shop.index') }}" class="btn btn-primary">
                <i class="fas fa-shopping-bag mr-2"></i>Continuer mes achats
            </a>
        </div>

    </div>

</div>

@endsection
