<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use Translatable;

    /** Text with a translation per language (see Translatable). */
    protected array $translatable = ['title', 'subtitle', 'description', 'button_text'];

    protected $fillable = [
        'type',
        'position',
        'title',
        'subtitle',
        'description',
        'image_path',
        'link_url',
        'button_text',
        'order',
        'is_active',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /** Active and inside its schedule (no date means no limit on that side). */
    public function scopeLive($q)
    {
        return $q->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('start_date')->orWhere('start_date', '<=', now()->toDateString()))
            ->where(fn ($w) => $w->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString()));
    }
}
