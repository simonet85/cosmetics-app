@extends('layouts.admin')

@section('title', 'Modifier la Bannière')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('admin.banners.index') }}" class="text-[#5a7c6f] hover:text-[#4a6c5f] mb-4 inline-block">
            <i class="fas fa-arrow-left mr-2"></i>Retour aux bannières
        </a>
        <h1 class="text-3xl font-bold">Modifier la Bannière</h1>
    </div>

    <form action="{{ route('admin.banners.update', $banner->id) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-md p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Title -->
            <div class="md:col-span-2">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    Titre <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" id="title" value="{{ old('title', $banner->title) }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent"
                       required>
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Subtitle -->
            <div class="md:col-span-2">
                <label for="subtitle" class="block text-sm font-medium text-gray-700 mb-2">
                    Sous-titre
                </label>
                <input type="text" name="subtitle" id="subtitle" value="{{ old('subtitle', $banner->subtitle) }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                @error('subtitle')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Description
                </label>
                <textarea name="description" id="description" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">{{ old('description', $banner->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Type -->
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                    Type <span class="text-red-500">*</span>
                </label>
                <select name="type" id="type"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent"
                        required>
                    <option value="hero" {{ old('type', $banner->type) === 'hero' ? 'selected' : '' }}>Hero</option>
                    <option value="promotional" {{ old('type', $banner->type) === 'promotional' ? 'selected' : '' }}>Promotionnel</option>
                    <option value="category" {{ old('type', $banner->type) === 'category' ? 'selected' : '' }}>Catégorie</option>
                    <option value="product" {{ old('type', $banner->type) === 'product' ? 'selected' : '' }}>Produit</option>
                </select>
                @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Position -->
            <div>
                <label for="position" class="block text-sm font-medium text-gray-700 mb-2">
                    Position <span class="text-red-500">*</span>
                </label>
                <select name="position" id="position"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent"
                        required>
                    <option value="home_slider" {{ old('position', $banner->position) === 'home_slider' ? 'selected' : '' }}>Slider Principal</option>
                    <option value="home_top" {{ old('position', $banner->position) === 'home_top' ? 'selected' : '' }}>Haut de Page</option>
                    <option value="home_middle" {{ old('position', $banner->position) === 'home_middle' ? 'selected' : '' }}>Milieu de Page</option>
                    <option value="home_bottom" {{ old('position', $banner->position) === 'home_bottom' ? 'selected' : '' }}>Bas de Page</option>
                    <option value="sidebar" {{ old('position', $banner->position) === 'sidebar' ? 'selected' : '' }}>Sidebar</option>
                </select>
                @error('position')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Current Image -->
            @if($banner->image_path)
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Image Actuelle
                    </label>
                    <img src="{{ asset($banner->image_path) }}"
                         alt="{{ $banner->title }}"
                         class="max-w-md rounded-lg shadow-md">
                </div>
            @endif

            <!-- Image -->
            <div class="md:col-span-2">
                <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                    Nouvelle Image
                </label>
                <input type="file" name="image" id="image" accept="image/*"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent"
                       onchange="previewImage(event)">
                @error('image')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">Laisser vide pour conserver l'image actuelle. Formats acceptés: JPEG, JPG, PNG, WEBP (max 2 Mo)</p>

                <!-- Image Preview -->
                <div id="image-preview" class="mt-4 hidden">
                    <img id="preview" src="" alt="Preview" class="max-w-md rounded-lg shadow-md">
                </div>
            </div>

            <!-- Link URL -->
            <div class="md:col-span-2">
                <label for="link_url" class="block text-sm font-medium text-gray-700 mb-2">
                    URL de Redirection
                </label>
                <input type="url" name="link_url" id="link_url" value="{{ old('link_url', $banner->link_url) }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent"
                       placeholder="https://example.com">
                @error('link_url')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Button Text -->
            <div>
                <label for="button_text" class="block text-sm font-medium text-gray-700 mb-2">
                    Texte du Bouton
                </label>
                <input type="text" name="button_text" id="button_text" value="{{ old('button_text', $banner->button_text) }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent"
                       placeholder="Ex: Découvrir">
                @error('button_text')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Order -->
            <div>
                <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                    Ordre d'Affichage <span class="text-red-500">*</span>
                </label>
                <input type="number" name="order" id="order" value="{{ old('order', $banner->order) }}"
                       min="0"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent"
                       required>
                @error('order')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">Plus le nombre est petit, plus la bannière apparaîtra en premier</p>
            </div>

            <!-- Start Date -->
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Date de Début
                </label>
                <input type="date" name="start_date" id="start_date"
                       value="{{ old('start_date', $banner->start_date ? $banner->start_date->format('Y-m-d') : '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                @error('start_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- End Date -->
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Date de Fin
                </label>
                <input type="date" name="end_date" id="end_date"
                       value="{{ old('end_date', $banner->end_date ? $banner->end_date->format('Y-m-d') : '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                @error('end_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Is Active -->
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $banner->is_active) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-[#5a7c6f] shadow-sm focus:border-[#5a7c6f] focus:ring focus:ring-[#5a7c6f] focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700">Bannière active</span>
                </label>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.banners.index') }}"
               class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                Annuler
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-[#5a7c6f] text-white rounded-lg hover:bg-[#4a6c5f] transition">
                <i class="fas fa-save mr-2"></i>Mettre à Jour
            </button>
        </div>
    </form>
</div>

<script>
function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview').src = e.target.result;
            document.getElementById('image-preview').classList.remove('hidden');
        }
        reader.readAsDataURL(file);
    }
}
</script>
@endsection
