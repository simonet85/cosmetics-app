@php
    $colors = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'processing' => 'bg-blue-100 text-blue-800',
        'shipped' => 'bg-purple-100 text-purple-800',
        'delivered' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800',
    ];
@endphp
<span class="inline-flex items-center rounded-full font-semibold {{ $size ?? 'px-2 py-1 text-xs leading-5' }} {{ $colors[$status] ?? 'bg-gray-100 text-gray-800' }}">
    {{ \App\Support\OrderLabels::status($status) }}
</span>
