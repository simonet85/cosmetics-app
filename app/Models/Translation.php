<?php

namespace App\Models;

use App\Support\Translations;
use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $fillable = ['translatable_type', 'translatable_id', 'field', 'locale', 'value', 'source_hash'];

    protected static function booted(): void
    {
        $forget = fn () => app(Translations::class)->flush();

        static::saved($forget);
        static::deleted($forget);
    }
}
