@extends('layouts.admin')

@section('title', 'Tableau de Bord')

@section('content')

{{-- Page Header --}}
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Tableau de Bord</h1>
    <p class="text-gray-600 mt-2">Bienvenue sur votre tableau de bord administrateur</p>
</div>

{{-- Statistics Cards --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    {{-- Total Revenue --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg">
                <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Ce mois</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ number_format($monthlyRevenue, 0) }} FCFA</h3>
        <p class="text-gray-600 text-sm mt-1">Revenus mensuels</p>
        <div class="mt-4 pt-4 border-t border-gray-200">
            <span class="text-sm text-gray-500">Total: {{ number_format($totalRevenue, 0) }} FCFA</span>
        </div>
    </div>

    {{-- Total Orders --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg">
                <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
            </div>
            @if($pendingOrders > 0)
            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded">{{ $pendingOrders }} en attente</span>
            @endif
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ $totalOrders }}</h3>
        <p class="text-gray-600 text-sm mt-1">Total des commandes</p>
        <div class="mt-4 pt-4 border-t border-gray-200">
            <span class="text-sm text-gray-500">{{ $completedOrders }} complétées</span>
        </div>
    </div>

    {{-- Total Products --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-lg">
                <i class="fas fa-box text-purple-600 text-xl"></i>
            </div>
            @if($lowStockProducts > 0)
            <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded">{{ $lowStockProducts }} stock bas</span>
            @endif
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ $totalProducts }}</h3>
        <p class="text-gray-600 text-sm mt-1">Total des produits</p>
        <div class="mt-4 pt-4 border-t border-gray-200">
            <span class="text-sm text-gray-500">{{ $activeProducts }} actifs</span>
        </div>
    </div>

    {{-- Total Customers --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center justify-center w-12 h-12 bg-orange-100 rounded-lg">
                <i class="fas fa-users text-orange-600 text-xl"></i>
            </div>
            @if($newCustomersThisMonth > 0)
            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">+{{ $newCustomersThisMonth }} ce mois</span>
            @endif
        </div>
        <h3 class="text-2xl font-bold text-gray-900">{{ $totalCustomers }}</h3>
        <p class="text-gray-600 text-sm mt-1">Total des clients</p>
        <div class="mt-4 pt-4 border-t border-gray-200">
            <span class="text-sm text-gray-500">{{ $totalReviews }} avis</span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    {{-- Revenue Chart --}}
    <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900">Revenus des 6 derniers mois</h2>
        </div>
        <div class="h-64">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Actions rapides</h2>
        <div class="space-y-3">
            <a href="{{ route('admin.products.create') }}" class="flex items-center gap-3 px-4 py-3 bg-[#5a7c6f] text-white rounded-lg hover:bg-[#4a6c5f] transition-colors">
                <i class="fas fa-plus-circle"></i>
                <span>Ajouter un produit</span>
            </a>
            <a href="{{ route('admin.orders.index') }}" class="flex items-center gap-3 px-4 py-3 bg-gray-100 text-gray-900 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-shopping-cart"></i>
                <span>Voir les commandes</span>
            </a>
            <a href="{{ route('admin.customers.index') }}" class="flex items-center gap-3 px-4 py-3 bg-gray-100 text-gray-900 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-users"></i>
                <span>Gérer les clients</span>
            </a>
            <a href="{{ route('admin.reviews.index') }}" class="flex items-center gap-3 px-4 py-3 bg-gray-100 text-gray-900 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-star"></i>
                <span>Modérer les avis</span>
                @if($pendingReviews > 0)
                <span class="ml-auto px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded">{{ $pendingReviews }}</span>
                @endif
            </a>
            <a href="{{ route('admin.coupons.create') }}" class="flex items-center gap-3 px-4 py-3 bg-gray-100 text-gray-900 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-ticket-alt"></i>
                <span>Créer un coupon</span>
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    {{-- Recent Orders --}}
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900">Commandes récentes</h2>
                <a href="{{ route('admin.orders.index') }}" class="text-[#5a7c6f] hover:text-[#4a6c5f] text-sm font-semibold">
                    Voir tout <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($recentOrders as $order)
            <div class="p-6 hover:bg-gray-50 transition-colors">
                <div class="flex items-center justify-between mb-2">
                    <a href="{{ route('admin.orders.show', $order->id) }}" class="font-semibold text-gray-900 hover:text-[#5a7c6f]">
                        {{ $order->order_number }}
                    </a>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                        @if($order->status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($order->status === 'processing') bg-blue-100 text-blue-800
                        @elseif($order->status === 'completed') bg-green-100 text-green-800
                        @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                        @endif">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <span>
                        <i class="fas fa-user mr-1"></i>
                        {{ $order->user ? $order->user->name : $order->customer_email }}
                    </span>
                    <span class="font-semibold text-gray-900">{{ number_format($order->total, 0) }} FCFA</span>
                </div>
                <div class="text-xs text-gray-500 mt-2">
                    <i class="fas fa-clock mr-1"></i>{{ $order->created_at->diffForHumans() }}
                </div>
            </div>
            @empty
            <div class="p-6 text-center text-gray-500">
                <i class="fas fa-inbox text-4xl mb-2"></i>
                <p>Aucune commande récente</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Top Products --}}
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900">Produits les plus vendus</h2>
                <a href="{{ route('admin.products.index') }}" class="text-[#5a7c6f] hover:text-[#4a6c5f] text-sm font-semibold">
                    Voir tout <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($topProducts as $product)
            <div class="p-6 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-4">
                    @if($product->images->count() > 0)
                    <img src="{{ asset($product->images->first()->path) }}" alt="{{ $product->name }}" class="w-16 h-16 object-cover rounded-lg">
                    @else
                    <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                        <i class="fas fa-image text-gray-400"></i>
                    </div>
                    @endif
                    <div class="flex-1">
                        <a href="{{ route('admin.products.edit', $product->id) }}" class="font-semibold text-gray-900 hover:text-[#5a7c6f] block">
                            {{ $product->name }}
                        </a>
                        <div class="flex items-center gap-4 mt-2 text-sm text-gray-600">
                            <span>
                                <i class="fas fa-shopping-bag mr-1"></i>
                                {{ $product->total_sales ?? 0 }} vendus
                            </span>
                            <span class="font-semibold text-gray-900">{{ number_format($product->price, 0) }} FCFA</span>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="p-6 text-center text-gray-500">
                <i class="fas fa-box-open text-4xl mb-2"></i>
                <p>Aucune vente enregistrée</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Revenue Chart
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const revenueData = @json($monthlyRevenueData);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: revenueData.map(item => item.month),
            datasets: [{
                label: 'Revenus (FCFA)',
                data: revenueData.map(item => item.revenue),
                borderColor: '#5a7c6f',
                backgroundColor: 'rgba(90, 124, 111, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#5a7c6f',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1f2937',
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    callbacks: {
                        label: function(context) {
                            return 'Revenus: ' + context.parsed.y.toFixed(0) + ' FCFA';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' FCFA';
                        }
                    },
                    grid: {
                        color: '#f3f4f6'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
</script>
@endpush
