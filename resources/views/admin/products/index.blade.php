@extends('layouts.admin')

@section('title', 'Gérer les produits')

@section('content')

{{-- Page Header --}}
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Produits</h1>
        <p class="text-gray-600 mt-2">Gérez vos produits et inventaire</p>
    </div>
    <a href="{{ route('admin.products.create') }}" class="bg-[#5a7c6f] hover:bg-[#4a6c5f] text-white font-semibold px-6 py-3 rounded-lg transition-colors flex items-center gap-2">
        <i class="fas fa-plus"></i>
        Ajouter un produit
    </a>
</div>

{{-- Filters --}}
<div class="bg-white rounded-lg shadow mb-6 p-6">
    <form method="GET" action="{{ route('admin.products.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        {{-- Search --}}
        <div>
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Rechercher..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f]"
            >
        </div>

        {{-- Category Filter --}}
        <div>
            <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f]">
                <option value="">Toutes les catégories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Status Filter --}}
        <div>
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f]">
                <option value="">Tous les statuts</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Brouillon</option>
            </select>
        </div>

        {{-- Actions --}}
        <div class="flex gap-2">
            <button type="submit" class="flex-1 bg-[#5a7c6f] hover:bg-[#4a6c5f] text-white font-semibold px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-search mr-2"></i>Filtrer
            </button>
            @if(request()->hasAny(['search', 'category', 'status']))
            <a href="{{ route('admin.products.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-times"></i>
            </a>
            @endif
        </div>
    </form>
</div>

{{-- Products Table --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Image</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produit</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">SKU</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Prix</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Stock</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Note</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($products as $product)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        @if($product->images->count() > 0)
                        <img src="{{ asset($product->images->first()->path) }}" alt="{{ $product->name }}" class="w-16 h-16 object-cover rounded-lg">
                        @else
                        <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                            <i class="fas fa-image text-gray-400"></i>
                        </div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div>
                            <a href="{{ route('admin.products.edit', $product) }}" class="font-semibold text-gray-900 hover:text-[#5a7c6f]">
                                {{ $product->name }}
                            </a>
                            @if($product->categories->count() > 0)
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach($product->categories->take(2) as $category)
                                <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">{{ $category->name }}</span>
                                @endforeach
                                @if($product->categories->count() > 2)
                                <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">+{{ $product->categories->count() - 2 }}</span>
                                @endif
                            </div>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $product->sku }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-semibold text-gray-900">{{ number_format($product->price, 0) }} FCFA</div>
                        @if($product->compare_price)
                        <div class="text-xs text-gray-500 line-through">{{ number_format($product->compare_price, 0) }} FCFA</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-semibold
                            @if($product->stock > 10) text-green-600
                            @elseif($product->stock > 0) text-yellow-600
                            @else text-red-600
                            @endif">
                            {{ $product->stock }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            @if($product->status === 'active') bg-green-100 text-green-800
                            @elseif($product->status === 'draft') bg-gray-100 text-gray-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($product->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @if($product->reviews_count > 0)
                        <div class="flex items-center gap-1">
                            <i class="fas fa-star text-yellow-400 text-sm"></i>
                            <span class="text-sm font-semibold text-gray-900">{{ number_format($product->reviews_avg_rating, 1) }}</span>
                            <span class="text-xs text-gray-500">({{ $product->reviews_count }})</span>
                        </div>
                        @else
                        <span class="text-xs text-gray-400">Aucun avis</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.products.edit', $product) }}" class="text-blue-600 hover:text-blue-800" title="Éditer">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="{{ route('shop.show', $product->slug) }}" target="_blank" class="text-gray-600 hover:text-gray-800" title="Voir">
                                <i class="fas fa-eye"></i>
                            </a>
                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-box-open text-4xl mb-4"></i>
                        <p class="text-lg">Aucun produit trouvé</p>
                        @if(request()->hasAny(['search', 'category', 'status']))
                        <a href="{{ route('admin.products.index') }}" class="text-[#5a7c6f] hover:text-[#4a6c5f] mt-2 inline-block">
                            Réinitialiser les filtres
                        </a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($products->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $products->appends(request()->query())->links() }}
    </div>
    @endif
</div>

@endsection
