@extends('layouts.admin')

@section('title', 'Gestion des Commandes')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-bold">Gestion des Commandes</h1>
            <p class="text-gray-600 mt-1">{{ $orders->total() }} commande(s) trouvée(s)</p>
        </div>
        <a href="{{ route('admin.orders.export', request()->query()) }}"
           class="bg-white border border-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-50 transition">
            <i class="fas fa-file-csv mr-2" aria-hidden="true"></i>Exporter en CSV
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-4 items-end">
            <div class="xl:col-span-2">
                <label for="filter-q" class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                <input type="search" id="filter-q" name="q" value="{{ $filters['q'] }}"
                       placeholder="N° commande, email, téléphone ou nom..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
            </div>

            <div>
                <label for="filter-status" class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                <select id="filter-status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
                    <option value="">Tous les statuts</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-payment" class="block text-sm font-medium text-gray-700 mb-2">Paiement</label>
                <select id="filter-payment" name="payment_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
                    <option value="">Tous les paiements</option>
                    @foreach ($payments as $value => $label)
                        <option value="{{ $value }}" @selected($filters['payment_status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-from" class="block text-sm font-medium text-gray-700 mb-2">Du</label>
                <input type="date" id="filter-from" name="from" value="{{ $filters['from'] }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
            </div>

            <div>
                <label for="filter-to" class="block text-sm font-medium text-gray-700 mb-2">Au</label>
                <input type="date" id="filter-to" name="to" value="{{ $filters['to'] }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
            </div>

            <div class="flex items-end gap-2 xl:col-span-2">
                <button type="submit" class="flex-1 bg-green text-white px-4 py-2 rounded-lg hover:bg-green-deep transition">
                    <i class="fas fa-search mr-2" aria-hidden="true"></i>Filtrer
                </button>
                <a href="{{ route('admin.orders.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition" title="Réinitialiser les filtres">
                    <i class="fas fa-redo" aria-hidden="true"></i><span class="sr-only">Réinitialiser les filtres</span>
                </a>
            </div>

            {{-- The current sort survives a new filter search --}}
            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
            <input type="hidden" name="direction" value="{{ $filters['direction'] }}">
        </form>
    </div>

    {{-- One form for the selection: both bulk buttons post the checked ids --}}
    <form method="POST" action="{{ route('admin.orders.bulk-status') }}" id="bulkForm">
        @csrf

        <div class="bg-white rounded-lg shadow-md p-4 mb-4 flex flex-wrap items-center gap-3">
            <span data-bulk-count class="text-sm text-gray-600">0 sélectionnée(s)</span>
            <label for="bulk-status" class="sr-only">Nouveau statut</label>
            <select id="bulk-status" name="status" class="px-3 py-2 border border-gray-300 rounded-lg">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-green text-white px-4 py-2 rounded-lg hover:bg-green-deep transition disabled:opacity-50" disabled>
                <i class="fas fa-check mr-2" aria-hidden="true"></i>Changer le statut
            </button>
            <button type="submit" formaction="{{ route('admin.orders.bulk-check-payments') }}" name="action" value="check-payments"
                    class="border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 transition disabled:opacity-50" disabled>
                <i class="fas fa-sync-alt mr-2" aria-hidden="true"></i>Vérifier les paiements
            </button>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 w-10">
                                <input type="checkbox" data-select-all aria-label="Tout sélectionner"
                                       class="rounded border-gray-300 text-green focus:ring-green">
                            </th>
                            @include('admin.orders.partials.sortable-th', ['column' => 'order_number', 'label' => 'N° commande'])
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Client</th>
                            @include('admin.orders.partials.sortable-th', ['column' => 'created_at', 'label' => 'Date'])
                            @include('admin.orders.partials.sortable-th', ['column' => 'total', 'label' => 'Total'])
                            @include('admin.orders.partials.sortable-th', ['column' => 'status', 'label' => 'Statut'])
                            @include('admin.orders.partials.sortable-th', ['column' => 'payment_status', 'label' => 'Paiement'])
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($orders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4">
                                    <input type="checkbox" name="ids[]" value="{{ $order->id }}"
                                           aria-label="Sélectionner la commande {{ $order->order_number }}"
                                           class="rounded border-gray-300 text-green focus:ring-green">
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="text-sm font-medium text-gray-900 hover:text-green-deep">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-sm text-gray-900">{{ $order->customerName() }}</div>
                                    <div class="text-sm text-gray-500">{{ $order->customer_email }}</div>
                                    @if ($order->customer_phone)
                                        <div class="text-sm text-gray-500">
                                            <a href="tel:{{ $order->customer_phone }}" class="hover:text-green-deep">{{ $order->customer_phone }}</a>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $order->created_at->format('d/m/Y') }}</div>
                                    <div class="text-sm text-gray-500">{{ $order->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ format_fcfa($order->total) }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    @include('admin.orders.partials.status-badge', ['status' => $order->status])
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    @include('admin.orders.partials.payment-badge', ['status' => $order->payment_status])
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="text-green hover:text-green-deep mr-3" title="Voir la commande">
                                        <i class="fas fa-eye" aria-hidden="true"></i><span class="sr-only">Voir la commande {{ $order->order_number }}</span>
                                    </a>
                                    <a href="{{ route('admin.orders.invoice', $order->id) }}" class="text-gray-600 hover:text-gray-900" title="Télécharger la facture">
                                        <i class="fas fa-file-pdf" aria-hidden="true"></i><span class="sr-only">Télécharger la facture {{ $order->order_number }}</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-shopping-cart text-4xl mb-2" aria-hidden="true"></i>
                                    <p>Aucune commande trouvée</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('bulkForm');
        if (!form) return;

        const boxes = () => Array.from(form.querySelectorAll('input[name="ids[]"]'));
        const buttons = form.querySelectorAll('button[type="submit"]');
        const counter = form.querySelector('[data-bulk-count]');

        function refresh() {
            const selected = boxes().filter((box) => box.checked).length;
            buttons.forEach((button) => { button.disabled = selected === 0; });
            if (counter) counter.textContent = selected + ' sélectionnée(s)';
        }

        form.addEventListener('change', refresh);
        form.addEventListener('submit', function (event) {
            const selected = boxes().filter((box) => box.checked).length;
            if (selected === 0) { event.preventDefault(); return; }

            const message = event.submitter && event.submitter.value === 'check-payments'
                ? 'Vérifier ' + selected + ' paiement(s) chez MoneyFusion ?'
                : 'Appliquer ce statut à ' + selected + ' commande(s) ?';

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
