<?php

namespace App\Http\Requests\Admin;

use App\Queries\OrderListQuery;
use App\Support\OrderLabels;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_keys(OrderLabels::statuses()))],
            'payment_status' => ['nullable', Rule::in(array_keys(OrderLabels::payments()))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', Rule::when($this->filled('from'), 'after_or_equal:from')],
            'sort' => ['nullable', Rule::in(OrderListQuery::SORTABLE)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * The validated filters, normalized so the query builder never has to guess.
     *
     * @return array<string, string|null>
     */
    public function filters(): array
    {
        return [
            'q' => trim((string) $this->query('q', '')),
            'status' => $this->query('status') ?: null,
            'payment_status' => $this->query('payment_status') ?: null,
            'from' => $this->query('from') ?: null,
            'to' => $this->query('to') ?: null,
            'sort' => $this->query('sort') ?: null,
            'direction' => $this->query('direction') === 'asc' ? 'asc' : 'desc',
        ];
    }
}
