@php
    $colors = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'paid' => 'bg-green-100 text-green-800',
        'failed' => 'bg-red-100 text-red-800',
        'refunded' => 'bg-gray-100 text-gray-800',
    ];
@endphp
<span class="inline-flex items-center rounded-full font-semibold {{ $size ?? 'px-2 py-1 text-xs leading-5' }} {{ $colors[$status] ?? 'bg-gray-100 text-gray-800' }}">
    {{ \App\Support\OrderLabels::payment($status) }}
</span>
