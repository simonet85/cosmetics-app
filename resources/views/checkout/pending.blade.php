@extends('layouts.app')

@section('title', 'Paiement en cours')

@section('content')
<div class="container mx-auto px-4 py-16">
    <div class="max-w-2xl mx-auto">

        {{-- Waiting --}}
        <div id="pending-box" class="bg-yellow-50 border-l-4 border-yellow-400 p-8 rounded-lg shadow-lg text-center {{ $failed ? 'hidden' : '' }}">
            <div class="mb-6">
                <i id="pending-icon" class="fas fa-circle-notch fa-spin text-6xl text-yellow-500"></i>
            </div>

            <h1 class="text-3xl font-bold text-gray-900 mb-4">Paiement en cours</h1>

            <p class="text-gray-700 mb-2">
                Nous attendons la confirmation de votre paiement par MoneyFusion.
                Cette page se met à jour toute seule : vous n'avez rien à faire.
            </p>
            <p id="pending-message" class="text-sm text-gray-600 mb-6" aria-live="polite">Vérification en cours...</p>

            <div class="bg-white p-6 rounded-lg shadow mb-6 text-left space-y-2">
                <p><span class="font-semibold">Numéro de commande :</span> {{ $order->order_number }}</p>
                <p><span class="font-semibold">Montant :</span> {{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
                <p><span class="font-semibold">Email :</span> {{ $order->customer_email }}</p>
            </div>

            <div class="space-x-4">
                <button type="button" id="check-now" class="inline-block bg-gray-900 text-white px-8 py-3 rounded-lg hover:bg-gray-800 transition">
                    Vérifier maintenant
                </button>
                <a href="{{ route('home') }}" class="inline-block bg-white text-gray-900 px-8 py-3 rounded-lg border-2 border-gray-900 hover:bg-gray-50 transition">
                    Retour à l'accueil
                </a>
            </div>
        </div>

        {{-- Failed / cancelled --}}
        <div id="failed-box" class="bg-red-50 border-l-4 border-red-400 p-8 rounded-lg shadow-lg text-center {{ $failed ? '' : 'hidden' }}">
            <div class="mb-6"><i class="fas fa-times-circle text-6xl text-red-500"></i></div>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Le paiement n'a pas abouti</h1>
            <p class="text-gray-700 mb-6">
                Votre commande {{ $order->order_number }} a été annulée et aucun montant ne vous a été débité.
                Si vous avez tout de même payé, votre commande sera rétablie automatiquement dès confirmation.
            </p>
            <a href="{{ route('checkout.index') }}" class="inline-block bg-gray-900 text-white px-8 py-3 rounded-lg hover:bg-gray-800 transition">
                Réessayer le paiement
            </a>
        </div>
    </div>
</div>

<script>
(function () {
    const statusUrl = @json($statusUrl);
    const pendingBox = document.getElementById('pending-box');
    const failedBox = document.getElementById('failed-box');
    const message = document.getElementById('pending-message');
    const button = document.getElementById('check-now');
    const startedAt = Date.now();
    let timer = null;
    let busy = false;

    // Every 5 s for the first 5 minutes, then every 30 s
    const nextDelay = () => (Date.now() - startedAt < 5 * 60 * 1000 ? 5000 : 30000);

    async function check() {
        if (busy) return;
        busy = true;
        clearTimeout(timer);
        button.disabled = true;

        let keepPolling = true;
        try {
            const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();

            if (data.state === 'paid') {
                message.textContent = data.message;
                window.location.href = data.redirect;
                return;
            }
            if (data.state === 'failed') {
                pendingBox.classList.add('hidden');
                failedBox.classList.remove('hidden');
                keepPolling = false;
            } else {
                message.textContent = data.message + ' (dernière vérification à ' + data.checked_at + ')';
            }
        } catch (error) {
            message.textContent = 'Connexion perdue, nouvel essai automatique...';
        } finally {
            busy = false;
            button.disabled = false;
            if (keepPolling) timer = setTimeout(check, nextDelay());
        }
    }

    button.addEventListener('click', check);
    check();
})();
</script>
@endsection
