@props(['orders'])

{{-- One row per order: number, date, states in words, total, link to the detail. --}}
<ul class="mt-4 divide-y divide-line border-y border-ink">
    @foreach ($orders as $order)
        <li>
            <a href="{{ route('account.orders.show', $order) }}" class="grid gap-1 py-4 hover:bg-mint sm:grid-cols-[1fr_auto] sm:items-center sm:gap-6 sm:px-3">
                <span>
                    <span class="block font-semibold">{{ $order->order_number }}</span>
                    <span class="block text-sm text-ink-muted">{{ $order->created_at->translatedFormat('j F Y') }} · {{ \App\Support\OrderLabels::status($order->status) }} · Paiement : {{ mb_strtolower(\App\Support\OrderLabels::payment($order->payment_status)) }}</span>
                </span>
                <span class="tnum flex items-center justify-between gap-4 sm:justify-end">
                    <span class="text-xl font-bold" style="font-stretch: 75%">{{ format_fcfa($order->total) }}</span>
                    <span class="underline">Voir<span class="sr-only"> la commande {{ $order->order_number }}</span></span>
                </span>
            </a>
        </li>
    @endforeach
</ul>
