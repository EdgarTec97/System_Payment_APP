<?php

namespace App\Console\Commands;

use App\Services\OrderCacheService;
use Illuminate\Console\Command;

class ClearOrderCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-orders {--user= : Clear cache for specific user} {--stats : Clear only statistics cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear order cache';

    /**
     * Execute the console command.
     */
    public function handle(OrderCacheService $cacheService): int
    {
        $userId = $this->option('user');
        $statsOnly = $this->option('stats');

        if ($userId) {
            $cacheService->clearUserCache((int) $userId);
            $this->info("Order cache for user {$userId} cleared successfully.");
        } elseif ($statsOnly) {
            $cacheService->clearStatisticsCache();
            $this->info('Order statistics cache cleared successfully.');
        } else {
            $cacheService->clearAllCache();
            $this->info('All order cache cleared successfully.');
        }

        return Command::SUCCESS;
    }
}
