@extends('layouts.admin')

@section('title', 'Ajouter un produit')

@section('content')

{{-- Page Header --}}
<div class="mb-8">
    <div class="flex items-center gap-3 mb-4">
        <a href="{{ route('admin.products.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Ajouter un produit</h1>
    </div>
</div>

<form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Basic Information --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Informations de base</h2>

                <div class="space-y-4">
                    {{-- Product Name --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Nom du produit *</label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('name') border-red-500 @enderror"
                            required
                        >
                        @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- SKU --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">SKU *</label>
                        <input
                            type="text"
                            name="sku"
                            value="{{ old('sku') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('sku') border-red-500 @enderror"
                            required
                        >
                        @error('sku')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Short Description --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Description courte</label>
                        <textarea
                            name="short_description"
                            rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('short_description') border-red-500 @enderror"
                        >{{ old('short_description') }}</textarea>
                        @error('short_description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Description complète *</label>
                        <textarea
                            name="description"
                            rows="8"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('description') border-red-500 @enderror"
                            required
                        >{{ old('description') }}</textarea>
                        @error('description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Pricing --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Prix</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Price --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Prix de vente *</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                            <input
                                type="number"
                                name="price"
                                value="{{ old('price') }}"
                                step="0.01"
                                min="0"
                                class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('price') border-red-500 @enderror"
                                required
                            >
                        </div>
                        @error('price')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Compare Price --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Prix comparé</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                            <input
                                type="number"
                                name="compare_price"
                                value="{{ old('compare_price') }}"
                                step="0.01"
                                min="0"
                                class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('compare_price') border-red-500 @enderror"
                            >
                        </div>
                        @error('compare_price')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Cost Price --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Prix coûtant</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                            <input
                                type="number"
                                name="cost_price"
                                value="{{ old('cost_price') }}"
                                step="0.01"
                                min="0"
                                class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('cost_price') border-red-500 @enderror"
                            >
                        </div>
                        @error('cost_price')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Inventory --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Inventaire</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Stock --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Quantité en stock *</label>
                        <input
                            type="number"
                            name="stock"
                            value="{{ old('stock', 0) }}"
                            min="0"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('stock') border-red-500 @enderror"
                            required
                        >
                        @error('stock')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Weight --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Poids (kg)</label>
                        <input
                            type="number"
                            name="weight"
                            value="{{ old('weight') }}"
                            step="0.01"
                            min="0"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('weight') border-red-500 @enderror"
                        >
                        @error('weight')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Dimensions --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Dimensions (L x W x H)</label>
                        <input
                            type="text"
                            name="dimensions"
                            value="{{ old('dimensions') }}"
                            placeholder="10 x 5 x 3 cm"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('dimensions') border-red-500 @enderror"
                        >
                        @error('dimensions')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- SEO --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">SEO</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Meta titre</label>
                        <input
                            type="text"
                            name="meta_title"
                            value="{{ old('meta_title') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f]"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Meta description</label>
                        <textarea
                            name="meta_description"
                            rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f]"
                        >{{ old('meta_description') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Meta mots-clés</label>
                        <input
                            type="text"
                            name="meta_keywords"
                            value="{{ old('meta_keywords') }}"
                            placeholder="cosmétique, beauté, soin"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f]"
                        >
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Status --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Publication</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Statut *</label>
                        <select
                            name="status"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f]"
                            required
                        >
                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Actif</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                            <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-3">
                        <input
                            type="checkbox"
                            name="is_featured"
                            id="is_featured"
                            value="1"
                            {{ old('is_featured') ? 'checked' : '' }}
                            class="w-5 h-5 text-[#5a7c6f] border-gray-300 rounded focus:ring-[#5a7c6f]"
                        >
                        <label for="is_featured" class="text-sm font-semibold text-gray-900">Produit en vedette</label>
                    </div>
                </div>
            </div>

            {{-- Categories --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Catégories</h2>

                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($categories as $category)
                    <div class="flex items-center gap-3">
                        <input
                            type="checkbox"
                            name="categories[]"
                            id="category-{{ $category->id }}"
                            value="{{ $category->id }}"
                            {{ in_array($category->id, old('categories', [])) ? 'checked' : '' }}
                            class="w-4 h-4 text-[#5a7c6f] border-gray-300 rounded focus:ring-[#5a7c6f]"
                        >
                        <label for="category-{{ $category->id }}" class="text-sm text-gray-900">{{ $category->name }}</label>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Tags --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Tags</h2>

                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($tags as $tag)
                    <div class="flex items-center gap-3">
                        <input
                            type="checkbox"
                            name="tags[]"
                            id="tag-{{ $tag->id }}"
                            value="{{ $tag->id }}"
                            {{ in_array($tag->id, old('tags', [])) ? 'checked' : '' }}
                            class="w-4 h-4 text-[#5a7c6f] border-gray-300 rounded focus:ring-[#5a7c6f]"
                        >
                        <label for="tag-{{ $tag->id }}" class="text-sm text-gray-900">{{ $tag->name }}</label>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Images --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Images</h2>

                <div>
                    <input
                        type="file"
                        name="images[]"
                        id="images"
                        multiple
                        accept="image/*"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f]"
                    >
                    <p class="text-xs text-gray-500 mt-2">Formats acceptés: JPG, PNG, WEBP. Max 2MB par image.</p>
                </div>

                <div id="imagePreview" class="mt-4 grid grid-cols-2 gap-2"></div>
            </div>

            {{-- Actions --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="space-y-3">
                    <button type="submit" class="w-full bg-[#5a7c6f] hover:bg-[#4a6c5f] text-white font-semibold py-3 rounded-lg transition-colors">
                        <i class="fas fa-save mr-2"></i>Créer le produit
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="block w-full text-center bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 rounded-lg transition-colors">
                        Annuler
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
    // Image preview
    document.getElementById('images').addEventListener('change', function(e) {
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';

        if (this.files) {
            Array.from(this.files).forEach((file, index) => {
                const reader = new FileReader();

                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative';
                    div.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-24 object-cover rounded-lg">
                        ${index === 0 ? '<span class="absolute top-1 left-1 px-2 py-1 bg-[#5a7c6f] text-white text-xs rounded">Principale</span>' : ''}
                    `;
                    preview.appendChild(div);
                };

                reader.readAsDataURL(file);
            });
        }
    });
</script>
@endpush
