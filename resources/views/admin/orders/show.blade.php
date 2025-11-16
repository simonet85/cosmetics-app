@extends('layouts.admin')

@section('title', 'Détails de la Commande #' . $order->order_number)

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('admin.orders.index') }}" class="text-[#5a7c6f] hover:text-[#4a6c5f] mb-4 inline-block">
            <i class="fas fa-arrow-left mr-2"></i>Retour aux commandes
        </a>
        <h1 class="text-3xl font-bold">Commande #{{ $order->order_number }}</h1>
        <p class="text-gray-600 mt-2">Passée le {{ $order->created_at->format('d/m/Y à H:i') }}</p>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Status Management -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Gestion du Statut</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Order Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Statut de la commande</label>
                        <form action="{{ route('admin.orders.update-status', $order->id) }}" method="POST" class="flex gap-2">
                            @csrf
                            @method('PUT')
                            <select name="status" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                                <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>En attente</option>
                                <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>En traitement</option>
                                <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>Expédiée</option>
                                <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Livrée</option>
                                <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Annulée</option>
                            </select>
                            <button type="submit" class="bg-[#5a7c6f] text-white px-4 py-2 rounded-lg hover:bg-[#4a6c5f] transition">
                                <i class="fas fa-save"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Payment Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Statut du paiement</label>
                        <form action="{{ route('admin.orders.update-payment-status', $order->id) }}" method="POST" class="flex gap-2">
                            @csrf
                            @method('PUT')
                            <select name="payment_status" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                                <option value="pending" {{ $order->payment_status === 'pending' ? 'selected' : '' }}>En attente</option>
                                <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>Payé</option>
                                <option value="failed" {{ $order->payment_status === 'failed' ? 'selected' : '' }}>Échoué</option>
                                <option value="refunded" {{ $order->payment_status === 'refunded' ? 'selected' : '' }}>Remboursé</option>
                            </select>
                            <button type="submit" class="bg-[#5a7c6f] text-white px-4 py-2 rounded-lg hover:bg-[#4a6c5f] transition">
                                <i class="fas fa-save"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Produits Commandés</h2>

                <div class="space-y-4">
                    @foreach($order->items as $item)
                        <div class="flex gap-4 pb-4 border-b last:border-b-0">
                            @if($item->product && $item->product->primaryImage)
                                <img src="{{ asset($item->product->primaryImage->path) }}"
                                     alt="{{ $item->product_name }}"
                                     class="w-20 h-20 object-cover rounded">
                            @else
                                <div class="w-20 h-20 bg-gray-200 rounded flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                            @endif

                            <div class="flex-1">
                                <h3 class="font-semibold">{{ $item->product_name }}</h3>
                                <p class="text-sm text-gray-600">SKU: {{ $item->sku }}</p>
                                <p class="text-sm text-gray-600">Quantité: {{ $item->quantity }}</p>
                            </div>

                            <div class="text-right">
                                <p class="font-semibold">{{ number_format($item->total, 2) }} FCFA</p>
                                <p class="text-sm text-gray-600">{{ number_format($item->price, 2) }} FCFA × {{ $item->quantity }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Customer Information -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Informations Client</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Contact</h3>
                        @if($order->user)
                            <p class="text-gray-900">{{ $order->user->first_name }} {{ $order->user->last_name }}</p>
                        @endif
                        <p class="text-gray-600">{{ $order->customer_email }}</p>
                        @if($order->customer_phone)
                            <p class="text-gray-600">{{ $order->customer_phone }}</p>
                        @endif
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Adresse de Livraison</h3>
                        @php
                            $shipping = json_decode($order->shipping_address, true);
                        @endphp
                        @if($shipping)
                            <p class="text-gray-900">{{ $shipping['first_name'] ?? '' }} {{ $shipping['last_name'] ?? '' }}</p>
                            <p class="text-gray-600">{{ $shipping['address'] ?? '' }}</p>
                            @if(isset($shipping['address_line_2']) && $shipping['address_line_2'])
                                <p class="text-gray-600">{{ $shipping['address_line_2'] }}</p>
                            @endif
                            <p class="text-gray-600">
                                {{ $shipping['city'] ?? '' }}, {{ $shipping['state'] ?? '' }} {{ $shipping['zip_code'] ?? '' }}
                            </p>
                            <p class="text-gray-600">{{ $shipping['country'] ?? '' }}</p>
                        @endif
                    </div>
                </div>

                @if($order->notes)
                    <div class="mt-4 pt-4 border-t">
                        <h3 class="font-semibold text-gray-700 mb-2">Notes</h3>
                        <p class="text-gray-600">{{ $order->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Order Summary -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Résumé</h2>

                <div class="space-y-2">
                    <div class="flex justify-between text-gray-600">
                        <span>Sous-total</span>
                        <span>{{ number_format($order->subtotal, 2) }} FCFA</span>
                    </div>

                    <div class="flex justify-between text-gray-600">
                        <span>Livraison</span>
                        <span>{{ number_format($order->shipping, 2) }} FCFA</span>
                    </div>

                    <div class="flex justify-between text-gray-600">
                        <span>TVA</span>
                        <span>{{ number_format($order->tax, 2) }} FCFA</span>
                    </div>

                    @if($order->discount > 0)
                        <div class="flex justify-between text-green-600">
                            <span>Réduction</span>
                            <span>-{{ number_format($order->discount, 2) }} FCFA</span>
                        </div>
                    @endif

                    <div class="flex justify-between text-xl font-bold pt-2 border-t">
                        <span>Total</span>
                        <span>{{ number_format($order->total, 2) }} FCFA</span>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Paiement</h2>

                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Méthode</span>
                        <span class="font-medium">
                            {{ getPaymentMethodLabel($order->payment_method) }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600">Statut</span>
                        <span>
                            @php
                                $paymentColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'paid' => 'bg-green-100 text-green-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                    'refunded' => 'bg-gray-100 text-gray-800',
                                ];
                                $paymentLabels = [
                                    'pending' => 'En attente',
                                    'paid' => 'Payé',
                                    'failed' => 'Échoué',
                                    'refunded' => 'Remboursé',
                                ];
                            @endphp
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $paymentColors[$order->payment_status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Order Status -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Statut</h2>

                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'processing' => 'bg-blue-100 text-blue-800',
                        'shipped' => 'bg-purple-100 text-purple-800',
                        'delivered' => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                    ];
                    $statusLabels = [
                        'pending' => 'En attente',
                        'processing' => 'En traitement',
                        'shipped' => 'Expédiée',
                        'delivered' => 'Livrée',
                        'cancelled' => 'Annulée',
                    ];
                @endphp
                <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ $statusLabels[$order->status] ?? $order->status }}
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
