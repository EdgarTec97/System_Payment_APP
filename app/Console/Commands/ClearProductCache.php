<?php

namespace App\Console\Commands;

use App\Services\ProductCacheService;
use Illuminate\Console\Command;

class ClearProductCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-products {--pattern= : Clear specific cache pattern}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear product cache';

    /**
     * Execute the console command.
     */
    public function handle(ProductCacheService $cacheService): int
    {
        $pattern = $this->option('pattern');

        if ($pattern) {
            $cacheService->clearCachePattern($pattern);
            $this->info("Product cache pattern '{$pattern}' cleared successfully.");
        } else {
            $cacheService->clearAllCache();
            $this->info('All product cache cleared successfully.');
        }

        return Command::SUCCESS;
    }
}
