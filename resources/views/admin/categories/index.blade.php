@extends('layouts.admin')

@section('title', 'Gérer les catégories')

@section('content')

{{-- Page Header --}}
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Catégories</h1>
        <p class="text-gray-600 mt-2">Gérez les catégories de produits</p>
    </div>
    <a href="{{ route('admin.categories.create') }}" class="bg-[#5a7c6f] hover:bg-[#4a6c5f] text-white font-semibold px-6 py-3 rounded-lg transition-colors flex items-center gap-2">
        <i class="fas fa-plus"></i>
        Ajouter une catégorie
    </a>
</div>

{{-- Categories Table --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Catégorie</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Slug</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Parent</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produits</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($categories as $category)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @if($category->icon)
                            <i class="{{ $category->icon }} text-[#5a7c6f] text-xl"></i>
                            @else
                            <div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center">
                                <i class="fas fa-folder text-gray-400"></i>
                            </div>
                            @endif
                            <div>
                                <p class="font-semibold text-gray-900">{{ $category->name }}</p>
                                @if($category->description)
                                <p class="text-sm text-gray-500 mt-1">{{ Str::limit($category->description, 50) }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $category->slug }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        @if($category->parent)
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">{{ $category->parent->name }}</span>
                        @else
                        <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-semibold text-gray-900">{{ $category->products_count }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            @if($category->is_active) bg-green-100 text-green-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="text-blue-600 hover:text-blue-800" title="Éditer">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie?')">
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
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-folder-open text-4xl mb-4"></i>
                        <p class="text-lg">Aucune catégorie trouvée</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($categories->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $categories->links() }}
    </div>
    @endif
</div>

@endsection
