{{-- MoneyFusion payment not confirmed yet: check on demand and every 30 s without reloading the page --}}
<div id="payment-check" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm">
    <p class="text-gray-700 mb-2">
        <i class="fas fa-clock text-yellow-600 mr-1"></i>
        Votre paiement MoneyFusion n'est pas encore confirmé.
    </p>
    <button type="button" id="payment-check-button" class="btn btn-outline w-full text-sm">
        <i class="fas fa-sync-alt mr-2"></i>Vérifier maintenant
    </button>
    <p id="payment-check-message" class="mt-2 text-xs text-gray-600" aria-live="polite"></p>
</div>

<script>
(function () {
    const statusUrl = @json($statusUrl);
    const button = document.getElementById('payment-check-button');
    const message = document.getElementById('payment-check-message');
    const icon = button.querySelector('i');
    let timer = null;
    let busy = false;

    async function check() {
        if (busy) return;
        busy = true;
        clearTimeout(timer);
        button.disabled = true;
        icon.classList.add('fa-spin');

        let keepPolling = true;
        try {
            const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();

            if (data.state === 'paid' || data.state === 'failed') {
                message.textContent = data.message;
                keepPolling = false;
                setTimeout(() => window.location.reload(), 800);
                return;
            }
            message.textContent = data.message + ' (dernière vérification à ' + data.checked_at + ')';
        } catch (error) {
            message.textContent = 'Vérification impossible pour le moment, nouvel essai automatique.';
        } finally {
            busy = false;
            button.disabled = false;
            icon.classList.remove('fa-spin');
            if (keepPolling) timer = setTimeout(check, 30000);
        }
    }

    button.addEventListener('click', check);
    timer = setTimeout(check, 30000);
})();
</script>
