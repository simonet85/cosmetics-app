<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    protected $fillable = ["name", "position", "photo", "bio", "email", "phone", "facebook", "twitter", "instagram", "linkedin", "order", "is_active"];
    protected $casts = ["is_active" => "boolean"];
    public function scopeActive($q) { return $q->where("is_active", true); }
}