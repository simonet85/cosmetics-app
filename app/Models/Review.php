<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ["product_id", "user_id", "rating", "title", "comment", "images", "is_verified_purchase", "is_approved", "helpful_count"];
    protected $casts = ["images" => "array", "is_verified_purchase" => "boolean", "is_approved" => "boolean"];
    
    public function product() { return $this->belongsTo(Product::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function votes() { return $this->hasMany(ReviewVote::class); }
    public function scopeApproved($q) { return $q->where("is_approved", true); }
}