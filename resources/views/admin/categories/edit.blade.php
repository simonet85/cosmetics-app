@extends('layouts.admin')

@section('title', 'Éditer une catégorie')

@section('content')

{{-- Page Header --}}
<div class="mb-8">
    <div class="flex items-center gap-3 mb-4">
        <a href="{{ route('admin.categories.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Éditer: {{ $category->name }}</h1>
    </div>
</div>

<form action="{{ route('admin.categories.update', $category) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Informations de base</h2>

                <div class="space-y-6">
                    {{-- Category Name --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Nom de la catégorie *</label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name', $category->name) }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('name') border-red-500 @enderror"
                            required
                        >
                        @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Slug --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Slug</label>
                        <input
                            type="text"
                            value="{{ $category->slug }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50"
                            disabled
                        >
                        <p class="text-xs text-gray-500 mt-1">Le slug sera automatiquement mis à jour si vous changez le nom</p>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Description</label>
                        <textarea
                            name="description"
                            rows="4"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('description') border-red-500 @enderror"
                        >{{ old('description', $category->description) }}</textarea>
                        @error('description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Icon --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Icône (Font Awesome)</label>
                        <input
                            type="text"
                            name="icon"
                            value="{{ old('icon', $category->icon) }}"
                            placeholder="fas fa-leaf"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('icon') border-red-500 @enderror"
                        >
                        @error('icon')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">Ex: fas fa-leaf, fas fa-spa, fas fa-shopping-bag</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Statistics --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Statistiques</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Produits:</span>
                        <span class="font-semibold text-gray-900">{{ $category->products()->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Sous-catégories:</span>
                        <span class="font-semibold text-gray-900">{{ $category->children()->count() }}</span>
                    </div>
                </div>
            </div>

            {{-- Parent Category --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Organisation</h2>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 mb-2">Catégorie parente</label>
                    <select
                        name="parent_id"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('parent_id') border-red-500 @enderror"
                    >
                        <option value="">Aucune (Catégorie principale)</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('parent_id', $category->parent_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Status --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Statut</h2>

                <div class="flex items-center gap-3">
                    <input
                        type="checkbox"
                        name="is_active"
                        id="is_active"
                        value="1"
                        {{ old('is_active', $category->is_active) ? 'checked' : '' }}
                        class="w-5 h-5 text-[#5a7c6f] border-gray-300 rounded focus:ring-[#5a7c6f]"
                    >
                    <label for="is_active" class="text-sm font-semibold text-gray-900">Catégorie active</label>
                </div>
            </div>

            {{-- Actions --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="space-y-3">
                    <button type="submit" class="w-full bg-[#5a7c6f] hover:bg-[#4a6c5f] text-white font-semibold py-3 rounded-lg transition-colors">
                        <i class="fas fa-save mr-2"></i>Enregistrer les modifications
                    </button>
                    <a href="{{ route('admin.categories.index') }}" class="block w-full text-center bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 rounded-lg transition-colors">
                        Annuler
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection
