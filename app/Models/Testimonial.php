<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = ["customer_name", "customer_photo", "comment", "rating", "show_on_home", "order", "is_active"];
    protected $casts = ["show_on_home" => "boolean", "is_active" => "boolean"];
    public function scopeActive($q) { return $q->where("is_active", true); }
}