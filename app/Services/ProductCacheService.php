<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductCacheService
{
    const CACHE_TTL = 3600; // 1 hour
    const CACHE_PREFIX = 'products:';
    const CACHE_TAG = 'products';

    /**
     * Get product by ID with caching.
     */
    public function getProduct(int $productId): ?Product
    {
        $cacheKey = self::CACHE_PREFIX . "single:{$productId}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($productId) {
            return Product::with(['images', 'primaryImage'])->find($productId);
        });
    }

    /**
     * Get active products with caching.
     */
    public function getActiveProducts(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = self::CACHE_PREFIX . "active:limit:{$limit}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($limit) {
            return Product::with(['primaryImage'])
                ->active()
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();
        });
    }

    /**
     * Get featured products with caching.
     */
    public function getFeaturedProducts(int $limit = 8): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = self::CACHE_PREFIX . "featured:limit:{$limit}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($limit) {
            return Product::with(['primaryImage'])
                ->active()
                ->where('discount', '>', 0)
                ->orderBy('discount', 'desc')
                ->take($limit)
                ->get();
        });
    }

    /**
     * Get products by category with caching.
     */
    public function getProductsByCategory(string $category, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = self::CACHE_PREFIX . "category:{$category}:limit:{$limit}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($category, $limit) {
            return Product::with(['primaryImage'])
                ->active()
                ->where('metadata->category', $category)
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();
        });
    }

    /**
     * Get price range with caching.
     */
    public function getPriceRange(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'price_range';

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL * 2, function () {
            return [
                'min' => Product::active()->min('final_price') ?? 0,
                'max' => Product::active()->max('final_price') ?? 1000,
            ];
        });
    }

    /**
     * Get product statistics with caching.
     */
    public function getProductStatistics(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'statistics';

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () {
            return [
                'total_products' => Product::count(),
                'active_products' => Product::active()->count(),
                'in_stock_products' => Product::inStock()->count(),
                'low_stock_products' => Product::lowStock()->count(),
                'out_of_stock_products' => Product::where('stock', 0)->count(),
                'average_price' => Product::active()->avg('final_price'),
                'total_stock_value' => Product::active()->sum(column: DB::raw('stock * final_price')),
            ];
        });
    }

    /**
     * Search products with caching.
     */
    public function searchProducts(string $query, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = self::CACHE_PREFIX . "search:" . md5($query) . ":limit:{$limit}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, 1800, function () use ($query, $limit) { // 30 minutes for search
            return Product::with(['primaryImage'])
                ->active()
                ->search($query)
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();
        });
    }

    /**
     * Get related products with caching.
     */
    public function getRelatedProducts(Product $product, int $limit = 4): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = self::CACHE_PREFIX . "related:{$product->id}:limit:{$limit}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($product, $limit) {
            $query = Product::with(['primaryImage'])
                ->active()
                ->where('id', '!=', $product->id);

            // Try to find products in the same category first
            if (isset($product->metadata['category'])) {
                $related = $query->where('metadata->category', $product->metadata['category'])
                    ->inRandomOrder()
                    ->take($limit)
                    ->get();

                if ($related->count() >= $limit) {
                    return $related;
                }
            }

            // Fallback to random products
            return $query->inRandomOrder()->take($limit)->get();
        });
    }

    /**
     * Cache product for quick access.
     */
    public function cacheProduct(Product $product): void
    {
        $cacheKey = self::CACHE_PREFIX . "single:{$product->id}";

        Cache::tags([self::CACHE_TAG])->put($cacheKey, $product->load(['images', 'primaryImage']), self::CACHE_TTL);
    }

    /**
     * Remove product from cache.
     */
    public function forgetProduct(int $productId): void
    {
        $cacheKey = self::CACHE_PREFIX . "single:{$productId}";
        Cache::tags([self::CACHE_TAG])->forget($cacheKey);
    }

    /**
     * Clear all product cache.
     */
    public function clearAllCache(): void
    {
        try {
            Cache::tags([self::CACHE_TAG])->flush();
            Log::info('Product cache cleared successfully');
        } catch (\Exception $e) {
            Log::error('Failed to clear product cache', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Clear specific cache patterns.
     */
    public function clearCachePattern(string $pattern): void
    {
        try {
            $keys = [
                self::CACHE_PREFIX . 'active:*',
                self::CACHE_PREFIX . 'featured:*',
                self::CACHE_PREFIX . 'category:*',
                self::CACHE_PREFIX . 'price_range',
                self::CACHE_PREFIX . 'statistics',
                self::CACHE_PREFIX . 'search:*',
                self::CACHE_PREFIX . 'related:*',
            ];

            foreach ($keys as $key) {
                if (fnmatch($pattern, $key)) {
                    Cache::tags([self::CACHE_TAG])->forget($key);
                }
            }

            Log::info('Product cache pattern cleared', ['pattern' => $pattern]);
        } catch (\Exception $e) {
            Log::error('Failed to clear product cache pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Warm up cache with popular products.
     */
    public function warmUpCache(): void
    {
        try {
            // Cache active products
            $this->getActiveProducts();

            // Cache featured products
            $this->getFeaturedProducts();

            // Cache price range
            $this->getPriceRange();

            // Cache statistics
            $this->getProductStatistics();

            Log::info('Product cache warmed up successfully');
        } catch (\Exception $e) {
            Log::error('Failed to warm up product cache', ['error' => $e->getMessage()]);
        }
    }
}
