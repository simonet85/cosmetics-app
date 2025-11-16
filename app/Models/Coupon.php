<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ["code", "type", "value", "min_amount", "max_discount", "start_date", "end_date", "usage_limit", "used_count", "is_active"];
    protected $casts = ["value" => "decimal:2", "min_amount" => "decimal:2", "max_discount" => "decimal:2", "start_date" => "date", "end_date" => "date", "is_active" => "boolean"];
    
    public function users() { return $this->belongsToMany(User::class, "coupon_user"); }
    public function scopeActive($q) { return $q->where("is_active", true); }
}