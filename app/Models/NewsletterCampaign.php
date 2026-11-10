<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NewsletterCampaign extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'subject',
        'body',
        'status',
        'max_subscriber_id',
        'last_subscriber_id',
        'recipients_count',
        'sent_count',
        'failed_count',
        'started_at',
        'sent_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'sent_at' => 'datetime',
        'recipients_count' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
    ];

    public function scopeSending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENDING);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSending(): bool
    {
        return $this->status === self::STATUS_SENDING;
    }
}
