<?php

namespace App\Queries;

use App\Models\ContactMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/** The admin message inbox: search, read state, and a whitelisted sort. */
class MessageListQuery
{
    public const SORTABLE = ['created_at', 'name', 'subject', 'is_read'];

    /**
     * @param  array<string, string|null>  $filters
     */
    public function build(array $filters): Builder
    {
        $query = ContactMessage::query();

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $like = '%'.addcslashes($search, '\\%_').'%';

            $query->where(function (Builder $q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('subject', 'like', $like)
                    ->orWhere('message', 'like', $like);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('is_read', $filters['status'] === 'read');
        }

        $sort = in_array($filters['sort'] ?? null, self::SORTABLE, true) ? $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        // Sequential ids break ties, so paginating never shows a row twice or skips one
        $query->orderBy($sort, $direction)->orderBy('id', $direction);

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
