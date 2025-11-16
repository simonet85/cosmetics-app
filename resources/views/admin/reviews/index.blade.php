@extends('layouts.admin')

@section('title', 'Gestion des Avis')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Gestion des Avis</h1>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('admin.reviews.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Titre ou commentaire..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                    <option value="">Tous les statuts</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approuvé</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Note</label>
                <select name="rating" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                    <option value="">Toutes les notes</option>
                    <option value="5" {{ request('rating') === '5' ? 'selected' : '' }}>5 étoiles</option>
                    <option value="4" {{ request('rating') === '4' ? 'selected' : '' }}>4 étoiles</option>
                    <option value="3" {{ request('rating') === '3' ? 'selected' : '' }}>3 étoiles</option>
                    <option value="2" {{ request('rating') === '2' ? 'selected' : '' }}>2 étoiles</option>
                    <option value="1" {{ request('rating') === '1' ? 'selected' : '' }}>1 étoile</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-[#5a7c6f] text-white px-4 py-2 rounded-lg hover:bg-[#4a6c5f] transition">
                    <i class="fas fa-search mr-2"></i>Filtrer
                </button>
                <a href="{{ route('admin.reviews.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Reviews List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        @forelse($reviews as $review)
            <div class="p-6 border-b last:border-b-0 hover:bg-gray-50">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-4 mb-2">
                            <!-- Rating -->
                            <div class="flex items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}"></i>
                                @endfor
                            </div>

                            <!-- Approval Status -->
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $review->is_approved ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $review->is_approved ? 'Approuvé' : 'En attente' }}
                            </span>

                            @if($review->is_verified_purchase)
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <i class="fas fa-check-circle mr-1"></i>Achat vérifié
                                </span>
                            @endif
                        </div>

                        <!-- Product -->
                        <div class="mb-2">
                            <a href="{{ route('products.show', $review->product->slug) }}"
                               target="_blank"
                               class="text-[#5a7c6f] hover:text-[#4a6c5f] font-medium">
                                <i class="fas fa-box mr-1"></i>{{ $review->product->name }}
                            </a>
                        </div>

                        <!-- Customer -->
                        <div class="text-sm text-gray-600 mb-2">
                            Par
                            <a href="{{ route('admin.customers.show', $review->user->id) }}"
                               class="text-[#5a7c6f] hover:text-[#4a6c5f]">
                                {{ $review->user->first_name }} {{ $review->user->last_name }}
                            </a>
                            le {{ $review->created_at->format('d/m/Y à H:i') }}
                        </div>

                        <!-- Title -->
                        @if($review->title)
                            <h3 class="font-semibold text-gray-900 mb-2">{{ $review->title }}</h3>
                        @endif

                        <!-- Comment -->
                        <p class="text-gray-700 mb-3">{{ $review->comment }}</p>

                        <!-- Images -->
                        @if($review->images)
                            @php
                                $images = is_string($review->images) ? json_decode($review->images, true) : $review->images;
                            @endphp
                            @if($images && count($images) > 0)
                                <div class="flex gap-2 mb-3">
                                    @foreach($images as $image)
                                        <img src="{{ asset($image) }}"
                                             alt="Review image"
                                             class="w-20 h-20 object-cover rounded cursor-pointer hover:opacity-75"
                                             onclick="window.open('{{ asset($image) }}', '_blank')">
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        <!-- Helpful Count -->
                        @if($review->helpful_count > 0)
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-thumbs-up mr-1"></i>{{ $review->helpful_count }} {{ $review->helpful_count > 1 ? 'personnes trouvent cet avis utile' : 'personne trouve cet avis utile' }}
                            </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 ml-4">
                        <form action="{{ route('admin.reviews.approve', $review->id) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="px-3 py-2 rounded-lg transition {{ $review->is_approved ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}"
                                    title="{{ $review->is_approved ? 'Retirer l\'approbation' : 'Approuver' }}">
                                <i class="fas {{ $review->is_approved ? 'fa-times' : 'fa-check' }}"></i>
                            </button>
                        </form>

                        <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition"
                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet avis ?')"
                                    title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="px-6 py-8 text-center text-gray-500">
                <i class="fas fa-comment-slash text-4xl mb-2"></i>
                <p>Aucun avis trouvé</p>
            </div>
        @endforelse

        @if($reviews->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
