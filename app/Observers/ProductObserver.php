<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\ProductCacheService;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    protected ProductCacheService $cacheService;

    public function __construct(ProductCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        $this->clearRelevantCache($product, 'created');
        $this->cacheService->cacheProduct($product);
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        $this->clearRelevantCache($product, 'updated');
        $this->cacheService->cacheProduct($product);
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        $this->clearRelevantCache($product, 'deleted');
        $this->cacheService->forgetProduct($product->id);
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        $this->clearRelevantCache($product, 'restored');
        $this->cacheService->cacheProduct($product);
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        $this->clearRelevantCache($product, 'force_deleted');
        $this->cacheService->forgetProduct($product->id);
    }

    /**
     * Clear relevant cache based on product changes.
     */
    private function clearRelevantCache(Product $product, string $action): void
    {
        try {
            // Always clear these caches when any product changes
            $this->cacheService->clearCachePattern('active:*');
            $this->cacheService->clearCachePattern('statistics');
            $this->cacheService->clearCachePattern('price_range');

            // Clear featured products cache if discount changed
            if ($action === 'updated' && $product->wasChanged('discount')) {
                $this->cacheService->clearCachePattern('featured:*');
            }

            // Clear category cache if category changed
            if ($action === 'updated' && $product->wasChanged('metadata')) {
                $this->cacheService->clearCachePattern('category:*');
            }

            // Clear search cache (it's relatively short-lived anyway)
            $this->cacheService->clearCachePattern('search:*');

            // Clear related products cache for this product
            $this->cacheService->clearCachePattern("related:{$product->id}:*");

            // If the product is popular, clear related cache for other products too
            if ($product->orderItems()->count() > 10) {
                $this->cacheService->clearCachePattern('related:*');
            }

            Log::info('Product cache cleared after model change', [
                'product_id' => $product->id,
                'action' => $action,
                'changed_attributes' => $product->getChanges(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to clear product cache after model change', [
                'product_id' => $product->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

