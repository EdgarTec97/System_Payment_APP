<?php

namespace App\Console\Commands;

use App\Services\OrderCacheService;
use App\Services\ProductCacheService;
use Illuminate\Console\Command;

class WarmUpCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm-up {--products : Warm up only product cache} {--orders : Warm up only order cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up application cache with frequently accessed data';

    /**
     * Execute the console command.
     */
    public function handle(ProductCacheService $productCache, OrderCacheService $orderCache): int
    {
        $productsOnly = $this->option('products');
        $ordersOnly = $this->option('orders');

        $this->info('Starting cache warm-up...');

        if ($productsOnly) {
            $this->warmUpProductCache($productCache);
        } elseif ($ordersOnly) {
            $this->warmUpOrderCache($orderCache);
        } else {
            $this->warmUpProductCache($productCache);
            $this->warmUpOrderCache($orderCache);
        }

        $this->info('Cache warm-up completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Warm up product cache.
     */
    private function warmUpProductCache(ProductCacheService $cacheService): void
    {
        $this->line('Warming up product cache...');

        try {
            $cacheService->warmUpCache();
            $this->info('✓ Product cache warmed up');
        } catch (\Exception $e) {
            $this->error('✗ Failed to warm up product cache: ' . $e->getMessage());
        }
    }

    /**
     * Warm up order cache.
     */
    private function warmUpOrderCache(OrderCacheService $cacheService): void
    {
        $this->line('Warming up order cache...');

        try {
            $cacheService->warmUpCache();
            $this->info('✓ Order cache warmed up');
        } catch (\Exception $e) {
            $this->error('✗ Failed to warm up order cache: ' . $e->getMessage());
        }
    }
}
