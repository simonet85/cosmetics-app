@extends('layouts.admin')

@section('title', 'Gestion des Avis')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Gestion des Avis</h1>
            <p class="text-gray-600 mt-1">{{ $reviews->total() }} avis trouvé(s)</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('admin.reviews.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label for="filter-search" class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                <input type="search" id="filter-search" name="search" value="{{ request('search') }}"
                       placeholder="Titre ou commentaire..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
            </div>

            <div>
                <label for="filter-status" class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select id="filter-status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
                    <option value="">Tous les statuts</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approuvé</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                </select>
            </div>

            <div>
                <label for="filter-rating" class="block text-sm font-medium text-gray-700 mb-2">Note</label>
                <select id="filter-rating" name="rating" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
                    <option value="">Toutes les notes</option>
                    @foreach ([5, 4, 3, 2, 1] as $rating)
                        <option value="{{ $rating }}" {{ request('rating') === (string) $rating ? 'selected' : '' }}>{{ $rating }} étoile{{ $rating > 1 ? 's' : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-verified" class="block text-sm font-medium text-gray-700 mb-2">Achat</label>
                <select id="filter-verified" name="verified" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
                    <option value="">Tous les avis</option>
                    <option value="1" {{ request('verified') === '1' ? 'selected' : '' }}>Achat vérifié</option>
                    <option value="0" {{ request('verified') === '0' ? 'selected' : '' }}>Sans achat vérifié</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-green text-white px-4 py-2 rounded-lg hover:bg-green-deep transition">
                    <i class="fas fa-search mr-2" aria-hidden="true"></i>Filtrer
                </button>
                <a href="{{ route('admin.reviews.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition" title="Réinitialiser les filtres">
                    <i class="fas fa-redo" aria-hidden="true"></i><span class="sr-only">Réinitialiser les filtres</span>
                </a>
            </div>
        </form>
    </div>

    {{-- One form for the selection and the per-review actions: no form may be nested in another --}}
    <form method="POST" action="{{ route('admin.reviews.bulk') }}" id="reviewBulkForm">
        @csrf

        <div class="bg-white rounded-lg shadow-md p-4 mb-4 flex flex-wrap items-center gap-3">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" data-select-all aria-label="Tout sélectionner"
                       class="rounded border-gray-300 text-green focus:ring-green">
                Tout sélectionner
            </label>
            <span data-bulk-count class="text-sm text-gray-600">0 sélectionné(s)</span>
            <label for="bulk-action" class="sr-only">Action</label>
            <select id="bulk-action" name="action" class="px-3 py-2 border border-gray-300 rounded-lg">
                <option value="approve">Approuver</option>
                <option value="unapprove">Retirer l'approbation</option>
                <option value="delete">Supprimer</option>
            </select>
            <button type="submit" class="bg-green text-white px-4 py-2 rounded-lg hover:bg-green-deep transition disabled:opacity-50" disabled>
                <i class="fas fa-check mr-2" aria-hidden="true"></i>Appliquer
            </button>
        </div>

        <!-- Reviews List -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            @forelse($reviews as $review)
            <div class="p-6 border-b last:border-b-0 hover:bg-gray-50">
                <div class="flex items-start gap-4 mb-3">
                    <input type="checkbox" name="ids[]" value="{{ $review->id }}"
                           aria-label="Sélectionner l'avis de {{ $review->user->first_name }} {{ $review->user->last_name }}"
                           class="mt-1 rounded border-gray-300 text-green focus:ring-green">

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
                               class="text-green hover:text-green-deep font-medium">
                                <i class="fas fa-box mr-1"></i>{{ $review->product->name }}
                            </a>
                        </div>

                        <!-- Customer -->
                        <div class="text-sm text-gray-600 mb-2">
                            Par
                            <a href="{{ route('admin.customers.show', $review->user->id) }}"
                               class="text-green hover:text-green-deep">
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

                    <!-- Actions: submit inside the bulk form, overridden to this review's route -->
                    <div class="flex gap-2 ml-4">
                        <button type="submit"
                                formaction="{{ route('admin.reviews.approve', $review->id) }}"
                                formmethod="post" name="_method" value="PATCH"
                                class="px-3 py-2 rounded-lg transition {{ $review->is_approved ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}"
                                title="{{ $review->is_approved ? 'Retirer l\'approbation' : 'Approuver' }}">
                            <i class="fas {{ $review->is_approved ? 'fa-times' : 'fa-check' }}" aria-hidden="true"></i>
                            <span class="sr-only">{{ $review->is_approved ? 'Retirer l\'approbation' : 'Approuver' }}</span>
                        </button>

                        <button type="submit"
                                formaction="{{ route('admin.reviews.destroy', $review->id) }}"
                                formmethod="post" name="_method" value="DELETE"
                                class="px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition"
                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet avis ?')"
                                title="Supprimer">
                            <i class="fas fa-trash" aria-hidden="true"></i>
                            <span class="sr-only">Supprimer cet avis</span>
                        </button>
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
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('reviewBulkForm');
        if (!form) return;

        const boxes = () => Array.from(form.querySelectorAll('input[name="ids[]"]'));
        const button = form.querySelector('button[type="submit"]:not([formaction])');
        const counter = form.querySelector('[data-bulk-count]');
        const action = form.querySelector('select[name="action"]');

        function refresh() {
            const selected = boxes().filter((box) => box.checked).length;
            if (button) button.disabled = selected === 0;
            if (counter) counter.textContent = selected + ' sélectionné(s)';
        }

        form.addEventListener('change', refresh);
        form.addEventListener('submit', function (event) {
            // Per-review buttons carry a formaction: they are single actions, not the bulk one
            if (event.submitter && event.submitter.hasAttribute('formaction')) return;

            const selected = boxes().filter((box) => box.checked).length;
            if (selected === 0) { event.preventDefault(); return; }

            const labels = {
                approve: 'Approuver ' + selected + ' avis ?',
                unapprove: 'Retirer l\'approbation de ' + selected + ' avis ?',
                delete: 'Supprimer définitivement ' + selected + ' avis ?',
            };
            const message = labels[action?.value] || 'Appliquer cette action à ' + selected + ' avis ?';

            if (!confirm(message)) event.preventDefault();
        });

        const all = form.querySelector('[data-select-all]');
        if (all) {
            all.addEventListener('change', function () {
                boxes().forEach((box) => { box.checked = all.checked; });
                refresh();
            });
        }

        refresh();
    })();
</script>
@endpush
