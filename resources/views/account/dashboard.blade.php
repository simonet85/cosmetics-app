@extends('layouts.app')

@section('content')

{{-- Page Header --}}
<div class="bg-gray-50 py-12 mb-12">
    <div class="container-custom">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Mon compte</h1>
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">Dashboard</span>
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
                        <h3 class="font-semibold text-gray-900">{{ $user->first_name }} {{ $user->last_name }}</h3>
                        <p class="text-sm text-gray-600">{{ $user->email }}</p>
                    </div>
                </div>

                <nav class="space-y-2">
                    <a href="{{ route('account.dashboard') }}" class="flex items-center gap-3 px-4 py-3 bg-gray-100 text-gray-900 rounded-lg font-medium">
                        <i class="fas fa-home w-5"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('account.orders') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg">
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
        <div class="lg:col-span-3 space-y-8">
            {{-- Welcome Message --}}
            <div class="bg-gradient-to-r from-[#5a7c6f] to-[#4a6c5f] rounded-lg shadow-sm p-8 text-white">
                <h2 class="text-2xl font-bold mb-2">Bienvenue, {{ $user->first_name }}!</h2>
                <p class="text-white/90">Gérez vos commandes et mettez à jour vos informations personnelles</p>
            </div>

            {{-- Statistics Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shopping-bag text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">{{ $totalOrders }}</h3>
                    <p class="text-gray-600 text-sm">Total des commandes</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">{{ $pendingOrders }}</h3>
                    <p class="text-gray-600 text-sm">Commandes en attente</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">{{ number_format($totalSpent, 0) }} FCFA</h3>
                    <p class="text-gray-600 text-sm">Total dépensé</p>
                </div>
            </div>

            {{-- Recent Orders --}}
            <div class="bg-white rounded-lg shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-900">Commandes récentes</h2>
                        <a href="{{ route('account.orders') }}" class="text-[#5a7c6f] hover:text-[#4a6c5f] text-sm font-semibold">
                            Voir tout <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                @if($recentOrders->count() > 0)
                <div class="divide-y divide-gray-200">
                    @foreach($recentOrders as $order)
                    <div class="p-6 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <a href="{{ route('account.orders.show', $order->id) }}" class="text-lg font-semibold text-gray-900 hover:text-[#5a7c6f]">
                                    {{ $order->order_number }}
                                </a>
                                <p class="text-sm text-gray-600 mt-1">
                                    <i class="fas fa-calendar mr-1"></i>
                                    {{ $order->created_at->format('d/m/Y à H:i') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-bold text-gray-900">{{ number_format($order->total, 0) }} FCFA</p>
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold mt-2
                                    @if($order->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($order->status === 'processing') bg-blue-100 text-blue-800
                                    @elseif($order->status === 'completed') bg-green-100 text-green-800
                                    @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            @foreach($order->items->take(3) as $item)
                            @if($item->product && $item->product->primaryImage)
                            <img
                                src="{{ asset($item->product->primaryImage->path) }}"
                                alt="{{ $item->product_name }}"
                                class="w-16 h-16 object-cover rounded"
                            >
                            @else
                            <div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center">
                                <i class="fas fa-image text-gray-400"></i>
                            </div>
                            @endif
                            @endforeach
                            @if($order->items->count() > 3)
                            <span class="text-sm text-gray-600">+{{ $order->items->count() - 3 }} autres</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="p-12 text-center text-gray-500">
                    <i class="fas fa-shopping-bag text-5xl mb-4 text-gray-300"></i>
                    <p class="text-lg mb-4">Vous n'avez pas encore de commandes</p>
                    <a href="{{ route('shop.index') }}" class="btn btn-primary">
                        <i class="fas fa-shopping-bag mr-2"></i>Commencer vos achats
                    </a>
                </div>
                @endif
            </div>

            {{-- Quick Actions --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="{{ route('account.profile') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-edit text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Modifier mon profil</h3>
                            <p class="text-sm text-gray-600">Mettez à jour vos informations personnelles</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('shop.index') }}" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shopping-bag text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Continuer mes achats</h3>
                            <p class="text-sm text-gray-600">Découvrez nos produits</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
