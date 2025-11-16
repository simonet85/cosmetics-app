@extends('layouts.app')

@section('content')

{{-- Page Header --}}
<div class="bg-gray-50 py-12 mb-12">
    <div class="container-custom">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Mes commandes</h1>
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <a href="{{ route('account.dashboard') }}" class="text-gray-600 hover:text-gray-900">Mon compte</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">Commandes</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        {{-- Sidebar --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center gap-3 mb-6 pb-6 border-b border-gray-200">
                    <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-gray-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</h3>
                        <p class="text-sm text-gray-600">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <nav class="space-y-2">
                    <a href="{{ route('account.dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-home w-5"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('account.orders') }}" class="flex items-center gap-3 px-4 py-3 bg-gray-100 text-gray-900 rounded-lg font-medium">
                        <i class="fas fa-shopping-bag w-5"></i>
                        <span>Mes commandes</span>
                    </a>
                    <a href="{{ route('account.profile') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-user-edit w-5"></i>
                        <span>Mon profil</span>
                    </a>
                    <a href="{{ route('wishlist.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-heart w-5"></i>
                        <span>Ma liste de souhaits</span>
                    </a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg">
                            <i class="fas fa-sign-out-alt w-5"></i>
                            <span>Déconnexion</span>
                        </button>
                    </form>
                </nav>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="lg:col-span-3">
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-900">Historique des commandes</h2>
                </div>

                @if($orders->count() > 0)
                    <div class="divide-y divide-gray-200">
                        @foreach($orders as $order)
                        <div class="p-6 hover:bg-gray-50 transition-colors">
                            {{-- Order Header --}}
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                                <div>
                                    <a href="{{ route('account.orders.show', $order->id) }}" class="text-xl font-bold text-gray-900 hover:text-[#5a7c6f]">
                                        {{ $order->order_number }}
                                    </a>
                                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-600">
                                        <span>
                                            <i class="fas fa-calendar mr-1"></i>
                                            {{ $order->created_at->format('d/m/Y à H:i') }}
                                        </span>
                                        <span>
                                            <i class="fas fa-box mr-1"></i>
                                            {{ $order->items->count() }} article(s)
                                        </span>
                                    </div>
                                </div>

                                <div class="flex flex-col items-end gap-2">
                                    <p class="text-2xl font-bold text-gray-900">{{ number_format($order->total, 0) }} FCFA</p>
                                    <div class="flex gap-2">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold
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
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                                            @if($order->payment_status === 'paid') bg-green-100 text-green-800
                                            @elseif($order->payment_status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($order->payment_status === 'failed') bg-red-100 text-red-800
                                            @endif">
                                            @if($order->payment_status === 'paid') Payé
                                            @elseif($order->payment_status === 'pending') En attente
                                            @elseif($order->payment_status === 'failed') Échoué
                                            @else {{ ucfirst($order->payment_status) }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Order Items Preview --}}
                            <div class="flex items-center gap-3 mb-4">
                                @foreach($order->items->take(4) as $item)
                                @if($item->product && $item->product->primaryImage)
                                <img
                                    src="{{ asset($item->product->primaryImage->path) }}"
                                    alt="{{ $item->product_name }}"
                                    class="w-16 h-16 object-cover rounded border border-gray-200"
                                >
                                @else
                                <div class="w-16 h-16 bg-gray-200 rounded border border-gray-200 flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                                @endif
                                @endforeach
                                @if($order->items->count() > 4)
                                <div class="w-16 h-16 bg-gray-100 rounded flex items-center justify-center text-gray-600 text-sm font-semibold">
                                    +{{ $order->items->count() - 4 }}
                                </div>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div class="flex gap-3">
                                <a href="{{ route('account.orders.show', $order->id) }}" class="btn btn-outline btn-sm">
                                    <i class="fas fa-eye mr-2"></i>Voir les détails
                                </a>
                                @if($order->status === 'completed')
                                <a href="{{ route('shop.index') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-redo mr-2"></i>Recommander
                                </a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if($orders->hasPages())
                    <div class="p-6 border-t border-gray-200">
                        {{ $orders->links() }}
                    </div>
                    @endif

                @else
                    <div class="p-12 text-center text-gray-500">
                        <i class="fas fa-shopping-bag text-5xl mb-4 text-gray-300"></i>
                        <p class="text-xl font-semibold mb-2">Aucune commande trouvée</p>
                        <p class="text-gray-600 mb-6">Vous n'avez pas encore passé de commande</p>
                        <a href="{{ route('shop.index') }}" class="btn btn-primary">
                            <i class="fas fa-shopping-bag mr-2"></i>Découvrir nos produits
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
