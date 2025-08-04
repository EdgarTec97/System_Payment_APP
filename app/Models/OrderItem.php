<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'product_title',
        'product_description',
        'quantity',
        'unit_price',
        'discount',
        'total_price',
        'product_snapshot',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'product_snapshot' => 'array',
    ];

    /**
     * Get the order that owns the item.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product associated with the item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Update quantity and recalculate total.
     */
    public function updateQuantity(int $quantity): void
    {
        $this->update([
            'quantity' => $quantity,
            'total_price' => $quantity * $this->unit_price,
        ]);

        $this->order->recalculateTotal();
    }

    /**
     * Get formatted unit price.
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return '$' . number_format($this->unit_price, 2);
    }

    /**
     * Get formatted total price.
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return '$' . number_format($this->total_price, 2);
    }

    /**
     * Get product image URL from snapshot or current product.
     */
    public function getProductImageUrlAttribute(): ?string
    {
        if ($this->product && $this->product->primary_image_url) {
            return $this->product->primary_image_url;
        }

        // Fallback to placeholder
        return asset('images/placeholder-product.png');
    }

    /**
     * Check if the product is still available.
     */
    public function isProductAvailable(): bool
    {
        return $this->product && $this->product->isAvailable();
    }

    /**
     * Check if the product has enough stock.
     */
    public function hasEnoughStock(): bool
    {
        return $this->product && $this->product->stock >= $this->quantity;
    }
}

