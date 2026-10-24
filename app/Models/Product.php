<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes, Translatable;

    /** Text with a translation per language (see Translatable). */
    protected array $translatable = ['name', 'short_description', 'full_description', 'how_to_use', 'ingredients'];

    /**
     * sku and slug carry database-level unique indexes that still apply to soft-deleted rows.
     * Soft-deleting a product renames both so the values can be reused, and restoring undoes it.
     */
    public static function deletedSuffix(int $id): string
    {
        return '-deleted-'.$id;
    }

    protected static function booted(): void
    {
        static::deleting(function (Product $product) {
            if ($product->isForceDeleting()) {
                return;
            }

            $suffix = static::deletedSuffix($product->id);

            foreach (['sku', 'slug'] as $column) {
                if (! str_ends_with($product->$column, $suffix)) {
                    $product->$column .= $suffix;
                }
            }

            $product->saveQuietly();
        });

        static::restoring(function (Product $product) {
            $suffix = static::deletedSuffix($product->id);

            foreach (['sku', 'slug'] as $column) {
                if (! str_ends_with($product->$column, $suffix)) {
                    continue;
                }

                $original = substr($product->$column, 0, -strlen($suffix));
                $taken = static::withTrashed()->where($column, $original)->where('id', '!=', $product->id)->exists();

                // Keep the suffixed value if someone has taken the original in the meantime.
                if (! $taken) {
                    $product->$column = $original;
                }
            }
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'price',
        'discount_price',
        'discount_percentage',
        'stock',
        'is_featured',
        'is_hot',
        'is_best_seller',
        'short_description',
        'full_description',
        'how_to_use',
        'ingredients',
        'is_active',
        'is_archived',
        'views_count',
        'sales_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_hot' => 'boolean',
        'is_best_seller' => 'boolean',
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
    ];

    // Relations
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Accessors
    public function getPrimaryImageAttribute()
    {
        return $this->images()->where('is_primary', true)->first()
            ?? $this->images()->first();
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function getTotalReviewsAttribute()
    {
        return $this->reviews()->count();
    }

    /**
     * Admin-facing status derived from the is_active / is_archived flags.
     */
    public function getStatusAttribute(): string
    {
        if ($this->is_archived) {
            return 'archived';
        }

        return $this->is_active ? 'active' : 'inactive';
    }

    public function getFinalPriceAttribute()
    {
        return $this->discount_price > 0 ? $this->discount_price : $this->price;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_archived', false);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeHot($query)
    {
        return $query->where('is_hot', true);
    }

    public function scopeBestSeller($query)
    {
        return $query->where('is_best_seller', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }
}
