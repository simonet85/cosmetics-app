@extends('layouts.admin')

@section('title', 'Messages')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Messages</h1>
            <p class="text-gray-600">Messages envoyés depuis le formulaire de contact. {{ $unread }} non {{ $unread > 1 ? 'lus' : 'lu' }}.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('admin.messages.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 md:items-end">
            <div class="md:col-span-2">
                <label for="filter-q" class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                <input id="filter-q" type="search" name="q" value="{{ $filters['q'] }}"
                       placeholder="Nom, e-mail, sujet ou contenu..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
            </div>

            <div>
                <label for="filter-status" class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select id="filter-status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
                    <option value="">Tous les messages</option>
                    <option value="unread" {{ $filters['status'] === 'unread' ? 'selected' : '' }}>Non lus</option>
                    <option value="read" {{ $filters['status'] === 'read' ? 'selected' : '' }}>Lus</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-green text-white px-4 py-2 rounded-lg hover:bg-green-deep transition">
                    <i class="fas fa-search mr-2" aria-hidden="true"></i>Filtrer
                </button>
                <a href="{{ route('admin.messages.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition" title="Réinitialiser">
                    <i class="fas fa-redo" aria-hidden="true"></i><span class="sr-only">Réinitialiser</span>
                </a>
            </div>
        </form>
    </div>

    {{-- One form for the selection and the bulk action --}}
    <form method="POST" action="{{ route('admin.messages.bulk') }}" id="messageBulkForm">
        @csrf

        <div class="bg-white rounded-lg shadow-md p-4 mb-4 flex flex-wrap items-center gap-3">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" data-select-all aria-label="Tout sélectionner"
                       class="rounded border-gray-300 text-green focus:ring-green">
                Tout sélectionner
            </label>
            <span data-bulk-count class="text-sm text-gray-600">0 sélectionné(s)</span>
            <label for="message-bulk-action" class="sr-only">Action</label>
            <select id="message-bulk-action" name="action" class="px-3 py-2 border border-gray-300 rounded-lg">
                <option value="read">Marquer comme lu</option>
                <option value="unread">Marquer comme non lu</option>
                <option value="delete">Supprimer</option>
            </select>
            <button type="submit" class="bg-green text-white px-4 py-2 rounded-lg hover:bg-green-deep transition disabled:opacity-50" disabled>
                <i class="fas fa-check mr-2" aria-hidden="true"></i>Appliquer
            </button>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            @if ($messages->isEmpty())
                <div class="px-6 py-12 text-center text-gray-500">
                    <i class="fas fa-envelope-open-text text-4xl mb-2" aria-hidden="true"></i>
                    <p>Aucun message trouvé.</p>
                </div>
            @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3"><span class="sr-only">Sélection</span></th>
                            @include('admin.orders.partials.sortable-th', ['route' => 'admin.messages.index', 'column' => 'name', 'label' => 'Expéditeur'])
                            @include('admin.orders.partials.sortable-th', ['route' => 'admin.messages.index', 'column' => 'subject', 'label' => 'Sujet'])
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Message</th>
                            @include('admin.orders.partials.sortable-th', ['route' => 'admin.messages.index', 'column' => 'created_at', 'label' => 'Reçu le'])
                            @include('admin.orders.partials.sortable-th', ['route' => 'admin.messages.index', 'column' => 'is_read', 'label' => 'Statut'])
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($messages as $message)
                            <tr class="{{ $message->is_read ? 'bg-white' : 'bg-green-50/40 font-medium' }}">
                                <td class="px-4 py-4">
                                    <input type="checkbox" name="ids[]" value="{{ $message->id }}"
                                           aria-label="Sélectionner le message « {{ $message->subject ?: 'sans sujet' }} »"
                                           class="rounded border-gray-300 text-green focus:ring-green">
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-block w-2 h-2 rounded-full mr-2 {{ $message->is_read ? 'bg-gray-300' : 'bg-green' }}" aria-hidden="true"></span>
                                    <span class="{{ $message->is_read ? 'text-gray-900' : 'font-bold text-gray-900' }}">{{ $message->name }}</span>
                                    <div class="text-sm font-normal">
                                        <a href="mailto:{{ $message->email }}" class="text-green hover:text-green-deep">{{ $message->email }}</a>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <a href="{{ route('admin.messages.show', $message) }}" class="text-gray-900 hover:text-green-deep">
                                        {{ $message->subject ?: '(sans sujet)' }}
                                    </a>
                                    @unless ($message->is_read)<span class="sr-only"> (non lu)</span>@endunless
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600 font-normal">{{ \Illuminate\Support\Str::limit($message->message, 120) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 font-normal">{{ $message->created_at->translatedFormat('j F Y à H:i') }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $message->is_read ? 'bg-gray-100 text-gray-700' : 'bg-green-100 text-green-800' }}">
                                        {{ $message->is_read ? 'Lu' : 'Non lu' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right text-sm">
                                    <a href="{{ route('admin.messages.show', $message) }}" class="inline-flex items-center px-3 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition" title="Ouvrir">
                                        <i class="fas fa-envelope-open" aria-hidden="true"></i><span class="sr-only">Ouvrir</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($messages->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $messages->links() }}
                </div>
            @endif
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('messageBulkForm');
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
            const selected = boxes().filter((box) => box.checked).length;
            if (selected === 0) { event.preventDefault(); return; }

            const labels = {
                read: 'Marquer comme lu ' + selected + ' message(s) ?',
                unread: 'Marquer comme non lu ' + selected + ' message(s) ?',
                delete: 'Supprimer définitivement ' + selected + ' message(s) ?',
            };
            const message = labels[action?.value] || 'Appliquer cette action à ' + selected + ' message(s) ?';

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
