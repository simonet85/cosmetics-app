@extends('layouts.admin')

@section('title', 'Détails du Client')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('admin.customers.index') }}" class="text-[#5a7c6f] hover:text-[#4a6c5f] mb-4 inline-block">
            <i class="fas fa-arrow-left mr-2"></i>Retour aux clients
        </a>
        <h1 class="text-3xl font-bold">{{ $customer->first_name }} {{ $customer->last_name }}</h1>
        <p class="text-gray-600 mt-2">Client depuis le {{ $customer->created_at->format('d/m/Y') }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Customer Information -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Informations Personnelles</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                        <p class="text-gray-900">{{ $customer->first_name }} {{ $customer->last_name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <p class="text-gray-900">{{ $customer->email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                        <p class="text-gray-900">{{ $customer->phone ?? '-' }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date de naissance</label>
                        <p class="text-gray-900">{{ $customer->date_of_birth ? \Carbon\Carbon::parse($customer->date_of_birth)->format('d/m/Y') : '-' }}</p>
                    </div>

                    @if($customer->address)
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                            <p class="text-gray-900">{{ $customer->address }}</p>
                            @if($customer->city || $customer->state || $customer->zip_code)
                                <p class="text-gray-900">
                                    {{ $customer->city }}{{ $customer->state ? ', ' . $customer->state : '' }}{{ $customer->zip_code ? ' ' . $customer->zip_code : '' }}
                                </p>
                            @endif
                            @if($customer->country)
                                <p class="text-gray-900">{{ $customer->country }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Commandes Récentes</h2>

                @if($customer->orders->count() > 0)
                    <div class="space-y-4">
                        @foreach($customer->orders->take(5) as $order)
                            <div class="flex items-center justify-between pb-4 border-b last:border-b-0">
                                <div class="flex-1">
                                    <a href="{{ route('admin.orders.show', $order->id) }}"
                                       class="text-[#5a7c6f] hover:text-[#4a6c5f] font-medium">
                                        #{{ $order->order_number }}
                                    </a>
                                    <p class="text-sm text-gray-600">{{ $order->created_at->format('d/m/Y à H:i') }}</p>
                                    <p class="text-sm text-gray-600">
                                        {{ $order->items->count() }} {{ $order->items->count() > 1 ? 'produits' : 'produit' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold">{{ number_format($order->total, 2) }} FCFA</p>
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
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $statusLabels[$order->status] ?? $order->status }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($customer->orders->count() > 5)
                        <div class="mt-4 text-center">
                            <a href="{{ route('admin.orders.index', ['search' => $customer->email]) }}"
                               class="text-[#5a7c6f] hover:text-[#4a6c5f]">
                                Voir toutes les commandes <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    @endif
                @else
                    <p class="text-gray-500 text-center py-4">Aucune commande</p>
                @endif
            </div>

            <!-- Recent Reviews -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Avis Récents</h2>

                @if($customer->reviews->count() > 0)
                    <div class="space-y-4">
                        @foreach($customer->reviews->take(5) as $review)
                            <div class="pb-4 border-b last:border-b-0">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <a href="{{ route('products.show', $review->product->slug) }}"
                                           class="text-[#5a7c6f] hover:text-[#4a6c5f] font-medium">
                                            {{ $review->product->name }}
                                        </a>
                                        <div class="flex items-center mt-1">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                                            @endfor
                                            <span class="ml-2 text-sm text-gray-600">{{ $review->created_at->format('d/m/Y') }}</span>
                                        </div>
                                    </div>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $review->is_approved ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $review->is_approved ? 'Approuvé' : 'En attente' }}
                                    </span>
                                </div>
                                @if($review->title)
                                    <h4 class="font-semibold mb-1">{{ $review->title }}</h4>
                                @endif
                                <p class="text-gray-600 text-sm">{{ Str::limit($review->comment, 150) }}</p>
                            </div>
                        @endforeach
                    </div>

                    @if($customer->reviews->count() > 5)
                        <div class="mt-4 text-center">
                            <a href="{{ route('admin.reviews.index', ['customer' => $customer->id]) }}"
                               class="text-[#5a7c6f] hover:text-[#4a6c5f]">
                                Voir tous les avis <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    @endif
                @else
                    <p class="text-gray-500 text-center py-4">Aucun avis</p>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Statistics -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Statistiques</h2>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                <i class="fas fa-shopping-cart text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Commandes</p>
                                <p class="text-xl font-bold">{{ $customer->orders_count }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                                <i class="fas fa-dollar-sign text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Total dépensé</p>
                                <p class="text-xl font-bold">{{ number_format($totalSpent, 2) }} FCFA</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center mr-3">
                                <i class="fas fa-star text-yellow-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Avis</p>
                                <p class="text-xl font-bold">{{ $customer->reviews_count }}</p>
                            </div>
                        </div>
                    </div>

                    @if($customer->orders_count > 0)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-chart-line text-purple-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Panier moyen</p>
                                    <p class="text-xl font-bold">{{ number_format($totalSpent / $customer->orders_count, 2) }} FCFA</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Account Status -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Statut du Compte</h2>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Statut</span>
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $customer->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $customer->is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>

                    @if($customer->hasAnyRole(['admin', 'super_admin']))
                        <div class="flex justify-between">
                            <span class="text-gray-600">Rôle</span>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                <i class="fas fa-shield-alt mr-1"></i>
                                {{ $customer->hasRole('super_admin') ? 'Super Admin' : 'Admin' }}
                            </span>
                        </div>
                    @endif

                    <div class="flex justify-between">
                        <span class="text-gray-600">Inscription</span>
                        <span class="text-gray-900">{{ $customer->created_at->format('d/m/Y') }}</span>
                    </div>

                    @if($customer->email_verified_at)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Email vérifié</span>
                            <span class="text-green-600">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            @if(!$customer->hasAnyRole(['admin', 'super_admin']) && $customer->orders_count === 0)
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Actions</h2>

                    <form action="{{ route('admin.customers.destroy', $customer->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition"
                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce client ?')">
                            <i class="fas fa-trash mr-2"></i>Supprimer le client
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
