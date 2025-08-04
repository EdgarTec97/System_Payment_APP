<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Product extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'stock',
        'price',
        'discount',
        'is_active',
        'sku',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'stock', 'price', 'discount', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the images for the product.
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Get the primary image for the product.
     */
    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Get the order items for the product.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Scope to filter active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter products in stock.
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Scope to filter products with low stock.
     */
    public function scopeLowStock($query, int $threshold = 5)
    {
        return $query->where('stock', '<=', $threshold)->where('stock', '>', 0);
    }

    /**
     * Scope to filter products by price range.
     */
    public function scopePriceRange($query, float $min = null, float $max = null)
    {
        if ($min !== null) {
            $query->where('final_price', '>=', $min);
        }
        
        if ($max !== null) {
            $query->where('final_price', '<=', $max);
        }
        
        return $query;
    }

    /**
     * Scope to search products by title or description.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'ILIKE', "%{$search}%")
              ->orWhere('description', 'ILIKE', "%{$search}%")
              ->orWhere('sku', 'ILIKE', "%{$search}%");
        });
    }

    /**
     * Get the final price after discount.
     */
    public function getFinalPriceAttribute(): float
    {
        return $this->price - ($this->price * $this->discount / 100);
    }

    /**
     * Get the discount amount.
     */
    public function getDiscountAmountAttribute(): float
    {
        return $this->price * $this->discount / 100;
    }

    /**
     * Check if product is in stock.
     */
    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Check if product has low stock.
     */
    public function hasLowStock(int $threshold = 5): bool
    {
        return $this->stock <= $threshold && $this->stock > 0;
    }

    /**
     * Check if product is available for purchase.
     */
    public function isAvailable(): bool
    {
        return $this->is_active && $this->isInStock();
    }

    /**
     * Decrease stock by given quantity.
     */
    public function decreaseStock(int $quantity): bool
    {
        if ($this->stock >= $quantity) {
            $this->decrement('stock', $quantity);
            return true;
        }
        
        return false;
    }

    /**
     * Increase stock by given quantity.
     */
    public function increaseStock(int $quantity): void
    {
        $this->increment('stock', $quantity);
    }

    /**
     * Get the primary image URL.
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        $primaryImage = $this->primaryImage;
        
        if ($primaryImage) {
            return $primaryImage->image_url ?: asset('storage/' . $primaryImage->image_path);
        }
        
        return null;
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get formatted final price.
     */
    public function getFormattedFinalPriceAttribute(): string
    {
        return '$' . number_format($this->final_price, 2);
    }

    /**
     * Get stock status label.
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'Sin stock';
        } elseif ($this->hasLowStock()) {
            return 'Stock bajo';
        } else {
            return 'En stock';
        }
    }

    /**
     * Get stock status color class.
     */
    public function getStockStatusColorAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'text-red-600';
        } elseif ($this->hasLowStock()) {
            return 'text-yellow-600';
        } else {
            return 'text-green-600';
        }
    }
}

