<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'image_path',
        'image_url',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Get the product that owns the image.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the full URL for the image.
     */
    public function getFullUrlAttribute(): string
    {
        if ($this->image_url) {
            return $this->image_url;
        }

        if ($this->image_path) {
            // If using S3, get the full URL
            if (config('filesystems.default') === 's3') {
                return Storage::disk('s3')->url($this->image_path);
            }
            
            // If using local storage
            return asset('storage/' . $this->image_path);
        }

        return asset('images/placeholder-product.png');
    }

    /**
     * Get optimized image URL for thumbnails.
     */
    public function getThumbnailUrlAttribute(): string
    {
        // For now, return the same URL
        // In production, you might want to implement image resizing
        return $this->full_url;
    }

    /**
     * Set as primary image and unset others.
     */
    public function setAsPrimary(): void
    {
        // Unset other primary images for this product
        self::where('product_id', $this->product_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Set this image as primary
        $this->update(['is_primary' => true]);
    }

    /**
     * Delete the image file from storage.
     */
    public function deleteFile(): bool
    {
        if ($this->image_path) {
            $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
            return Storage::disk($disk)->delete($this->image_path);
        }

        return true;
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        // When deleting an image, delete the file from storage
        static::deleting(function ($image) {
            $image->deleteFile();
        });

        // When creating an image, set as primary if it's the first image
        static::creating(function ($image) {
            if (!$image->product->images()->exists()) {
                $image->is_primary = true;
                $image->sort_order = 0;
            } else {
                $image->sort_order = $image->product->images()->max('sort_order') + 1;
            }
        });
    }
}

