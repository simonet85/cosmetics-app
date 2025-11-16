<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        "type",
        "position",
        "title",
        "subtitle",
        "description",
        "image_path",
        "link_url",
        "button_text",
        "order",
        "is_active",
        "start_date",
        "end_date"
    ];

    protected $casts = [
        "is_active" => "boolean",
        "start_date" => "date",
        "end_date" => "date"
    ];

    public function scopeActive($q)
    {
        return $q->where("is_active", true);
    }
}