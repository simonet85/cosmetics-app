<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = ["email", "subscribed_at", "unsubscribed_at", "token"];
    protected $casts = ["subscribed_at" => "datetime", "unsubscribed_at" => "datetime"];
}