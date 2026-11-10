<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ContactMessage extends Model
{
    public const UNREAD_CACHE_KEY = 'contact_messages.unread';

    protected $fillable = ['name', 'email', 'subject', 'message', 'admin_reply', 'replied_at', 'ip_address', 'is_read'];

    protected $casts = ['is_read' => 'boolean', 'replied_at' => 'datetime'];

    public function scopeUnread($q)
    {
        return $q->where('is_read', false);
    }

    /** The admin menu shows this on every page: one short-lived value instead of a query per screen. */
    public static function unreadCount(): int
    {
        return (int) Cache::remember(self::UNREAD_CACHE_KEY, now()->addMinutes(10), fn () => static::unread()->count());
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::UNREAD_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::UNREAD_CACHE_KEY));
    }
}
