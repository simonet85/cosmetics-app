<?php

namespace App\Queries;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * The admin order list: the same filters serve the page, the CSV export and the bulk actions,
 * so what the admin sees is exactly what they export.
 */
class OrderListQuery
{
    public const SORTABLE = ['order_number', 'created_at', 'total', 'status', 'payment_status'];

    /**
     * @param  array<string, string|null>  $filters
     */
    public function build(array $filters): Builder
    {
        $query = Order::query()->with('user');

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $like = '%'.addcslashes($search, '\\%_').'%';

            $query->where(function (Builder $q) use ($like) {
                $q->where('order_number', 'like', $like)
                    ->orWhere('customer_email', 'like', $like)
                    ->orWhere('customer_phone', 'like', $like)
                    // The shipping address is JSON; matching its text finds the guest's name without
                    // depending on the JSON path dialect (MySQL and SQLite) or on how it was encoded
                    ->orWhere('shipping_address', 'like', $like);
            });
        }

        foreach (['status', 'payment_status'] as $column) {
            if (! empty($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        $sort = in_array($filters['sort'] ?? null, self::SORTABLE, true) ? $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sort, $direction)
            ->orderBy('created_at', $direction)
            // Sequential ids break ties, so paginating never shows a row twice or skips one
            ->orderBy('id', $direction);

        return $query;
    }

    /**
     * @param  array<string, string|null>  $filters
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->build($filters)->paginate($perPage)->withQueryString();
    }
}
