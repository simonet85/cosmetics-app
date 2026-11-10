{{-- Sortable table header: keeps the filters, toggles the direction, shows the active one.
     Expects $column, $label and the $filters array from the controller; $route defaults to the order list. --}}
@php
    $routeName = $route ?? 'admin.orders.index';
    $active = ($filters['sort'] ?? 'created_at') === $column;
    $current = $filters['direction'] ?? 'desc';
    $next = $active ? ($current === 'asc' ? 'desc' : 'asc') : 'asc';
    $url = route($routeName, array_filter(
        array_merge($filters, ['sort' => $column, 'direction' => $next]),
        fn ($value) => $value !== null && $value !== ''
    ));
@endphp
<th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
    @if ($active) aria-sort="{{ $current === 'asc' ? 'ascending' : 'descending' }}" @endif>
    <a href="{{ $url }}" class="inline-flex items-center gap-1 hover:text-gray-900">
        {{ $label }}
        @if ($active)
            <i class="fas fa-sort-{{ $current === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
        @else
            <i class="fas fa-sort text-gray-300" aria-hidden="true"></i>
        @endif
    </a>
</th>
