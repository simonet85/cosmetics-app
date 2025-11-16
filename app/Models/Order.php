<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ["user_id", "order_number", "subtotal", "shipping", "tax", "discount", "total", "status", "payment_method", "moneyfusion_token", "moneyfusion_payment_url", "payment_status", "shipping_address", "billing_address", "notes", "customer_email", "customer_phone", "shipping_cost"];
    protected $casts = ["subtotal" => "decimal:2", "shipping" => "decimal:2", "tax" => "decimal:2", "discount" => "decimal:2", "total" => "decimal:2", "shipping_address" => "array", "billing_address" => "array"];
    
    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function moneyFusionPayment() { return $this->hasOne(\Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment::class, 'order_id'); }
    public function scopePaid($q) { return $q->where("payment_status", "paid"); }
}