<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Product;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
use App\Services\OrderCacheService;
use App\Services\ProductCacheService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register cache services as singletons
        $this->app->singleton(ProductCacheService::class, function ($app) {
            return new ProductCacheService();
        });

        $this->app->singleton(OrderCacheService::class, function ($app) {
            return new OrderCacheService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Product::observe(ProductObserver::class);
        Order::observe(OrderObserver::class);

        // Set pagination per page from config
        config(['app.pagination_per_page' => 15]);
        config(['app.max_product_images' => 3]);

        // Warm up cache in production
        if (app()->environment('production')) {
            $this->warmUpCache();
        }
    }

    /**
     * Warm up cache with essential data.
     */
    private function warmUpCache(): void
    {
        try {
            // Warm up product cache
            $productCache = app(ProductCacheService::class);
            $productCache->warmUpCache();

            // Warm up order cache
            $orderCache = app(OrderCacheService::class);
            $orderCache->warmUpCache();
        } catch (\Exception $e) {
            // Don't fail the application if cache warming fails
            logger()->error('Failed to warm up cache during boot', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
