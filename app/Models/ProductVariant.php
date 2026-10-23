<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'size',
        'color',
        'color_name',
        'color_hex',
        'price_modifier',
        'stock',
        'is_active',
    ];

    protected $casts = [
        'price_modifier' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Variant list price: the product's price plus this variant's modifier.
     */
    /**
     * Human-readable variant name, e.g. "100ml / Rose".
     */
    public function getLabelAttribute(): string
    {
        return collect([$this->size, $this->color_name ?: $this->color])->filter()->implode(' / ') ?: (string) $this->sku;
    }

    public function getPriceAttribute(): float
    {
        return (float) $this->product->price + (float) $this->price_modifier;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }
}
