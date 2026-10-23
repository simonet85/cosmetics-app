<?php

namespace App\Models;

use App\Casts\AddressCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'order_number', 'subtotal', 'shipping', 'tax', 'discount', 'total', 'status', 'payment_method', 'moneyfusion_token', 'moneyfusion_payment_url', 'payment_status', 'shipping_address', 'billing_address', 'notes', 'customer_email', 'customer_phone', 'shipping_cost', 'tracking_number', 'shipping_carrier', 'tracking_url'];

    protected $casts = ['subtotal' => 'decimal:2', 'shipping' => 'decimal:2', 'tax' => 'decimal:2', 'discount' => 'decimal:2', 'total' => 'decimal:2', 'shipping_address' => AddressCast::class, 'billing_address' => AddressCast::class, 'stock_released_at' => 'datetime'];

    /**
     * Link to the order confirmation page. Guests have no account to prove ownership with,
     * so the link is signed: without the signature the page is only visible to the owner.
     */
    public function successUrl(): string
    {
        return URL::signedRoute('checkout.success', ['order' => $this->id]);
    }

    /**
     * Give the order's stock back (product and variant). Safe to call repeatedly: the marker is set
     * with one conditional UPDATE, so only the first call, even a simultaneous one, restores anything.
     */
    public function releaseStock(): bool
    {
        $claimed = static::whereKey($this->id)->whereNull('stock_released_at')->update(['stock_released_at' => now()]);

        if (! $claimed) {
            return false;
        }

        foreach ($this->items()->get() as $item) {
            Product::withTrashed()->whereKey($item->product_id)->increment('stock', $item->quantity);

            if ($item->variant_id) {
                ProductVariant::whereKey($item->variant_id)->increment('stock', $item->quantity);
            }
        }

        $this->stock_released_at = now();

        return true;
    }

    /**
     * Take the stock of a previously released order again (an order that comes back to life).
     * Stock never goes below zero; a shortfall is logged, because the order is then oversold.
     */
    public function reserveStock(): bool
    {
        $claimed = static::whereKey($this->id)->whereNotNull('stock_released_at')->update(['stock_released_at' => null]);

        if (! $claimed) {
            return false;
        }

        foreach ($this->items()->get() as $item) {
            $product = Product::withTrashed()->find($item->product_id);

            if ($product && $product->stock < $item->quantity) {
                Log::warning('Order revived without enough stock', ['order_id' => $this->id, 'product_id' => $item->product_id, 'stock' => $product->stock, 'needed' => $item->quantity]);
            }

            Product::withTrashed()->whereKey($item->product_id)->update(['stock' => DB::raw('GREATEST(stock - '.(int) $item->quantity.', 0)')]);

            if ($item->variant_id) {
                ProductVariant::whereKey($item->variant_id)->update(['stock' => DB::raw('GREATEST(stock - '.(int) $item->quantity.', 0)')]);
            }
        }

        $this->stock_released_at = null;

        return true;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function moneyFusionPayment()
    {
        return $this->hasOne(\Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment::class, 'order_id');
    }

    public function scopePaid($q)
    {
        return $q->where('payment_status', 'paid');
    }
}
