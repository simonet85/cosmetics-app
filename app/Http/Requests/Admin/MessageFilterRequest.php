<?php

namespace App\Http\Requests\Admin;

use App\Queries\MessageListQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MessageFilterRequest extends FormRequest
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
            'status' => ['nullable', Rule::in(['unread', 'read'])],
            'sort' => ['nullable', Rule::in(MessageListQuery::SORTABLE)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function filters(): array
    {
        return [
            'q' => trim((string) $this->query('q', '')),
            'status' => $this->query('status') ?: null,
            'sort' => $this->query('sort') ?: null,
            'direction' => $this->query('direction') === 'asc' ? 'asc' : 'desc',
        ];
    }
}
