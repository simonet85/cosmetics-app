@extends('layouts.admin')

@section('title', 'Campagnes newsletter')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-bold">Campagnes newsletter</h1>
            <p class="text-gray-600">{{ $activeSubscribers }} abonné(s) actif(s)</p>
        </div>
        <a href="{{ route('admin.campaigns.create') }}" class="bg-green text-white px-4 py-2 rounded-lg hover:bg-green-deep transition">
            <i class="fas fa-plus mr-2" aria-hidden="true"></i>Nouvelle campagne
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Sujet</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Destinataires</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Envoyés / échecs</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Dates</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($campaigns as $campaign)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $campaign->subject }}</div>
                                <div class="text-sm text-gray-500">{{ \Illuminate\Support\Str::limit($campaign->body, 80) }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                @php
                                    [$badge, $label] = match ($campaign->status) {
                                        'draft' => ['bg-gray-100 text-gray-800', 'Brouillon'],
                                        'sending' => ['bg-blue-100 text-blue-800', 'Envoi en cours'],
                                        'sent' => ['bg-green-100 text-green-800', 'Envoyée'],
                                        'cancelled' => ['bg-red-100 text-red-800', 'Annulée'],
                                        default => ['bg-gray-100 text-gray-800', $campaign->status],
                                    };
                                @endphp
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $badge }}">{{ $label }}</span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $campaign->recipients_count ?? $activeSubscribers }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                <span class="text-green-deep font-medium">{{ $campaign->sent_count }}</span>
                                @if ($campaign->failed_count > 0)
                                    <span class="text-red-700"> / {{ $campaign->failed_count }} échec(s)</span>
                                @endif
                                @if ($campaign->isSending() && $campaign->recipients_count)
                                    @php $done = $campaign->sent_count + $campaign->failed_count; @endphp
                                    <div class="w-32 bg-gray-200 rounded-full h-1.5 mt-1">
                                        <div class="bg-green h-1.5 rounded-full" style="width: {{ min(100, round($done / max(1, $campaign->recipients_count) * 100)) }}%"></div>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">{{ $done }} / {{ $campaign->recipients_count }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div>Créée le {{ $campaign->created_at->format('d/m/Y H:i') }}</div>
                                @if ($campaign->sent_at)
                                    <div class="text-gray-500">Envoyée le {{ $campaign->sent_at->format('d/m/Y H:i') }}</div>
                                @elseif ($campaign->started_at)
                                    <div class="text-gray-500">Démarrée le {{ $campaign->started_at->format('d/m/Y H:i') }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('admin.campaigns.preview', $campaign) }}" target="_blank" rel="noopener"
                                   class="text-gray-600 hover:text-gray-900 mr-3" title="Aperçu">
                                    <i class="fas fa-eye" aria-hidden="true"></i><span class="sr-only">Aperçu de la campagne</span>
                                </a>

                                @if ($campaign->isDraft())
                                    <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="text-green hover:text-green-deep mr-3" title="Modifier">
                                        <i class="fas fa-edit" aria-hidden="true"></i><span class="sr-only">Modifier la campagne</span>
                                    </a>
                                    <form action="{{ route('admin.campaigns.send', $campaign) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Envoyer cette campagne à {{ $activeSubscribers }} abonné(s) actif(s) ?')">
                                        @csrf
                                        <button type="submit" class="text-blue-600 hover:text-blue-900 mr-3" title="Envoyer">
                                            <i class="fas fa-paper-plane" aria-hidden="true"></i><span class="sr-only">Envoyer la campagne</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.campaigns.destroy', $campaign) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Supprimer définitivement ce brouillon ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Supprimer">
                                            <i class="fas fa-trash" aria-hidden="true"></i><span class="sr-only">Supprimer la campagne</span>
                                        </button>
                                    </form>
                                @endif

                                @if ($campaign->isSending())
                                    <form action="{{ route('admin.campaigns.cancel', $campaign) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Annuler l\'envoi ? Les destinataires déjà servis ne seront pas recontactés.')">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Annuler l'envoi">
                                            <i class="fas fa-stop-circle" aria-hidden="true"></i><span class="sr-only">Annuler l'envoi</span>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-paper-plane text-4xl mb-2" aria-hidden="true"></i>
                                <p>Aucune campagne pour le moment</p>
                                <p class="text-sm mt-1">Créez un brouillon, vérifiez l'aperçu, puis lancez l'envoi.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($campaigns->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
