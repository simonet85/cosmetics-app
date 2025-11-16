@extends('layouts.admin')

@section('title', 'Gestion des Bannières')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Gestion des Bannières</h1>
        <a href="{{ route('admin.banners.create') }}" class="bg-[#5a7c6f] text-white px-4 py-2 rounded-lg hover:bg-[#4a6c5f] transition">
            <i class="fas fa-plus mr-2"></i>Nouvelle Bannière
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('admin.banners.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                    <option value="">Tous les types</option>
                    <option value="hero" {{ request('type') === 'hero' ? 'selected' : '' }}>Hero</option>
                    <option value="promotional" {{ request('type') === 'promotional' ? 'selected' : '' }}>Promotionnel</option>
                    <option value="category" {{ request('type') === 'category' ? 'selected' : '' }}>Catégorie</option>
                    <option value="product" {{ request('type') === 'product' ? 'selected' : '' }}>Produit</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                <select name="position" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                    <option value="">Toutes les positions</option>
                    <option value="home_slider" {{ request('position') === 'home_slider' ? 'selected' : '' }}>Slider Principal</option>
                    <option value="home_top" {{ request('position') === 'home_top' ? 'selected' : '' }}>Haut de Page</option>
                    <option value="home_middle" {{ request('position') === 'home_middle' ? 'selected' : '' }}>Milieu de Page</option>
                    <option value="home_bottom" {{ request('position') === 'home_bottom' ? 'selected' : '' }}>Bas de Page</option>
                    <option value="sidebar" {{ request('position') === 'sidebar' ? 'selected' : '' }}>Sidebar</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                    <option value="">Tous les statuts</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-[#5a7c6f] text-white px-4 py-2 rounded-lg hover:bg-[#4a6c5f] transition">
                    <i class="fas fa-search mr-2"></i>Filtrer
                </button>
                <a href="{{ route('admin.banners.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Banners Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($banners as $banner)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Banner Image -->
                <div class="relative h-48 bg-gray-200">
                    @if($banner->image_path)
                        <img src="{{ asset($banner->image_path) }}"
                             alt="{{ $banner->title }}"
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                            <i class="fas fa-image text-4xl"></i>
                        </div>
                    @endif

                    <!-- Status Badge -->
                    <div class="absolute top-2 right-2">
                        @php
                            $isExpired = $banner->end_date && $banner->end_date->isPast();
                        @endphp
                        @if($isExpired)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                Expiré
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $banner->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $banner->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        @endif
                    </div>

                    <!-- Order Badge -->
                    <div class="absolute top-2 left-2">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            Ordre: {{ $banner->order }}
                        </span>
                    </div>
                </div>

                <!-- Banner Info -->
                <div class="p-4">
                    <h3 class="text-lg font-semibold mb-2 truncate">{{ $banner->title }}</h3>

                    @if($banner->subtitle)
                        <p class="text-sm text-gray-600 mb-2 truncate">{{ $banner->subtitle }}</p>
                    @endif

                    <div class="flex flex-wrap gap-2 mb-3">
                        <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                            {{ ucfirst($banner->type) }}
                        </span>
                        <span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800">
                            {{ str_replace('_', ' ', ucfirst($banner->position)) }}
                        </span>
                    </div>

                    @if($banner->start_date || $banner->end_date)
                        <div class="text-xs text-gray-500 mb-3">
                            @if($banner->start_date)
                                Du {{ $banner->start_date->format('d/m/Y') }}
                            @endif
                            @if($banner->end_date)
                                au {{ $banner->end_date->format('d/m/Y') }}
                            @endif
                        </div>
                    @endif

                    <div class="flex gap-2">
                        <a href="{{ route('admin.banners.edit', $banner->id) }}"
                           class="flex-1 text-center px-3 py-2 bg-[#5a7c6f] text-white rounded-lg hover:bg-[#4a6c5f] transition text-sm">
                            <i class="fas fa-edit mr-1"></i>Modifier
                        </a>
                        <form action="{{ route('admin.banners.destroy', $banner->id) }}" method="POST" class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm"
                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette bannière ?')">
                                <i class="fas fa-trash mr-1"></i>Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-lg shadow-md p-8 text-center text-gray-500">
                <i class="fas fa-image text-4xl mb-2"></i>
                <p>Aucune bannière trouvée</p>
                <a href="{{ route('admin.banners.create') }}" class="inline-block mt-4 text-[#5a7c6f] hover:text-[#4a6c5f]">
                    <i class="fas fa-plus mr-2"></i>Créer une bannière
                </a>
            </div>
        @endforelse
    </div>

    @if($banners->hasPages())
        <div class="mt-6">
            {{ $banners->links() }}
        </div>
    @endif
</div>
@endsection
