<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\OrderCacheService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    protected OrderCacheService $cacheService;

    public function __construct(OrderCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $this->clearRelevantCache($order, 'created');

        // Cache the order if it's not a draft
        if ($order->status !== 'draft') {
            $this->cacheService->cacheOrder($order);
        } else {
            // Cache as user cart if it's a draft
            $this->cacheService->cacheUserCart($order->user_id, $order);
        }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        $this->clearRelevantCache($order, 'updated');

        // Handle cart vs order caching
        if ($order->status === 'draft') {
            $this->cacheService->cacheUserCart($order->user_id, $order);
        } else {
            $this->cacheService->cacheOrder($order);

            // If status changed from draft, clear cart cache
            if ($order->wasChanged('status') && $order->getOriginal('status') === 'draft') {
                $this->cacheService->cacheUserCart($order->user_id, null);
            }
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        $this->clearRelevantCache($order, 'deleted');
        $this->cacheService->forgetOrder($order->id);

        // Clear cart cache if it was a draft
        if ($order->status === 'draft') {
            $this->cacheService->cacheUserCart($order->user_id, null);
        }
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        $this->clearRelevantCache($order, 'restored');
        $this->cacheService->cacheOrder($order);
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        $this->clearRelevantCache($order, 'force_deleted');
        $this->cacheService->forgetOrder($order->id);

        if ($order->status === 'draft') {
            $this->cacheService->cacheUserCart($order->user_id, null);
        }
    }

    /**
     * Clear relevant cache based on order changes.
     */
    private function clearRelevantCache(Order $order, string $action): void
    {
        try {
            // Always clear user-specific cache
            $this->cacheService->clearUserCache($order->user_id);

            // Clear statistics cache for any order change
            $this->cacheService->clearStatisticsCache();

            // Clear recent orders cache
            $this->cacheService->clearCachePattern('recent:*');

            // Clear status-specific cache if status changed
            if ($action === 'updated' && $order->wasChanged('status')) {
                $oldStatus = $order->getOriginal('status');
                $newStatus = $order->status;

                // Clear cache for both old and new status
                $this->cacheService->clearCachePattern("status:{$oldStatus}:*");
                $this->cacheService->clearCachePattern("status:{$newStatus}:*");

                Log::info('Order status changed, clearing status caches', [
                    'order_id' => $order->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]);
            }

            // Clear monthly statistics if the order date is in current year
            if ($order->created_at->year === date('Y')) {
                $this->cacheService->clearCachePattern('monthly_stats:*');
            }

            // Clear top customers cache if this affects customer rankings
            if (in_array($action, ['created', 'deleted', 'force_deleted']) && $order->status !== 'draft') {
                $this->cacheService->clearCachePattern('top_customers:*');
            }

            Log::info('Order cache cleared after model change', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'action' => $action,
                'status' => $order->status,
                'changed_attributes' => $order->getChanges(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear order cache after model change', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
