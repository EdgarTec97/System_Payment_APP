<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OrderCacheService
{
    const CACHE_TTL = 1800; // 30 minutes
    const CACHE_PREFIX = 'orders:';
    const CACHE_TAG = 'orders';

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
                self::CACHE_PREFIX . 'recent:*',
                self::CACHE_PREFIX . 'status',
                self::CACHE_PREFIX . 'status:*',
                self::CACHE_PREFIX . 'monthly_stats:*',
                self::CACHE_PREFIX . 'top_customers:*',
                self::CACHE_PREFIX . 'monthly_stats:*',
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
     * Get order by ID with caching.
     */
    public function getOrder(int $orderId): ?Order
    {
        $cacheKey = self::CACHE_PREFIX . "single:{$orderId}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($orderId) {
            return Order::with(['items.product.primaryImage', 'user'])->find($orderId);
        });
    }

    /**
     * Get user orders with caching.
     */
    public function getUserOrders(int $userId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = self::CACHE_PREFIX . "user:{$userId}:limit:{$limit}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($userId, $limit) {
            return Order::with(['items.product.primaryImage'])
                ->where('user_id', $userId)
                ->where('status', '!=', 'draft')
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();
        });
    }

    /**
     * Get recent orders with caching.
     */
    public function getRecentOrders(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = self::CACHE_PREFIX . "recent:limit:{$limit}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($limit) {
            return Order::with(['user', 'items'])
                ->where('status', '!=', 'draft')
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();
        });
    }

    /**
     * Get orders by status with caching.
     */
    public function getOrdersByStatus(string $status, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = self::CACHE_PREFIX . "status:{$status}:limit:{$limit}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($status, $limit) {
            return Order::with(['user', 'items.product'])
                ->byStatus($status)
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();
        });
    }

    /**
     * Get order statistics with caching.
     */
    public function getOrderStatistics(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'statistics';

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () {
            return [
                'total_orders' => Order::where('status', '!=', 'draft')->count(),
                'pending_orders' => Order::whereIn('status', ['created', 'paid'])->count(),
                'completed_orders' => Order::where('status', 'delivered')->count(),
                'cancelled_orders' => Order::where('status', 'cancelled')->count(),
                'total_revenue' => Order::where('status', '!=', 'draft')->sum('total'),
                'average_order_value' => Order::where('status', '!=', 'draft')->avg('total'),
                'orders_today' => Order::where('status', '!=', 'draft')
                    ->whereDate('created_at', today())
                    ->count(),
                'revenue_today' => Order::where('status', '!=', 'draft')
                    ->whereDate('created_at', today())
                    ->sum('total'),
            ];
        });
    }

    /**
     * Get user order statistics with caching.
     */
    public function getUserOrderStatistics(int $userId): array
    {
        $cacheKey = self::CACHE_PREFIX . "user_stats:{$userId}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            return [
                'total_orders' => Order::where('user_id', $userId)->where('status', '!=', 'draft')->count(),
                'pending_orders' => Order::where('user_id', $userId)->whereIn('status', ['created', 'paid'])->count(),
                'completed_orders' => Order::where('user_id', $userId)->where('status', 'delivered')->count(),
                'cancelled_orders' => Order::where('user_id', $userId)->where('status', 'cancelled')->count(),
                'total_spent' => Order::where('user_id', $userId)->where('status', '!=', 'draft')->sum('total'),
                'average_order_value' => Order::where('user_id', $userId)->where('status', '!=', 'draft')->avg('total'),
            ];
        });
    }

    /**
     * Get monthly order statistics with caching.
     */
    public function getMonthlyOrderStatistics(int $year = null): array
    {
        $year = $year ?? date('Y');
        $cacheKey = self::CACHE_PREFIX . "monthly_stats:{$year}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL * 2, function () use ($year) {
            $monthlyStats = [];

            for ($month = 1; $month <= 12; $month++) {
                $monthlyStats[$month] = [
                    'orders_count' => Order::where('status', '!=', 'draft')
                        ->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->count(),
                    'revenue' => Order::where('status', '!=', 'draft')
                        ->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->sum('total'),
                ];
            }

            return $monthlyStats;
        });
    }

    /**
     * Get top customers with caching.
     */
    public function getTopCustomers(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = self::CACHE_PREFIX . "top_customers:limit:{$limit}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, self::CACHE_TTL, function () use ($limit) {
            return User::withCount(['orders' => function ($query) {
                $query->where('status', '!=', 'draft');
            }])
                ->with(['orders' => function ($query) {
                    $query->where('status', '!=', 'draft');
                }])
                ->having('orders_count', '>', 0)
                ->orderBy('orders_count', 'desc')
                ->take($limit)
                ->get()
                ->map(function ($user) {
                    $user->total_spent = $user->orders->sum('total');
                    return $user;
                });
        });
    }

    /**
     * Get user's current cart with caching.
     */
    public function getUserCart(int $userId): ?Order
    {
        $cacheKey = self::CACHE_PREFIX . "cart:{$userId}";

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, 600, function () use ($userId) { // 10 minutes for cart
            return Order::with(['items.product.primaryImage'])
                ->where('user_id', $userId)
                ->where('status', 'draft')
                ->first();
        });
    }

    /**
     * Cache order for quick access.
     */
    public function cacheOrder(Order $order): void
    {
        $cacheKey = self::CACHE_PREFIX . "single:{$order->id}";

        Cache::tags([self::CACHE_TAG])->put(
            $cacheKey,
            $order->load(['items.product.primaryImage', 'user']),
            self::CACHE_TTL
        );
    }

    /**
     * Cache user cart.
     */
    public function cacheUserCart(int $userId, ?Order $cart): void
    {
        $cacheKey = self::CACHE_PREFIX . "cart:{$userId}";

        if ($cart) {
            Cache::tags([self::CACHE_TAG])->put($cacheKey, $cart->load(['items.product.primaryImage']), 600);
        } else {
            Cache::tags([self::CACHE_TAG])->forget($cacheKey);
        }
    }

    /**
     * Remove order from cache.
     */
    public function forgetOrder(int $orderId): void
    {
        $cacheKey = self::CACHE_PREFIX . "single:{$orderId}";
        Cache::tags([self::CACHE_TAG])->forget($cacheKey);
    }

    /**
     * Clear user-specific cache.
     */
    public function clearUserCache(int $userId): void
    {
        try {
            $patterns = [
                self::CACHE_PREFIX . "user:{$userId}:*",
                self::CACHE_PREFIX . "user_stats:{$userId}",
                self::CACHE_PREFIX . "cart:{$userId}",
            ];

            foreach ($patterns as $pattern) {
                Cache::tags([self::CACHE_TAG])->forget($pattern);
            }

            Log::info('User order cache cleared', ['user_id' => $userId]);
        } catch (\Exception $e) {
            Log::error('Failed to clear user order cache', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear all order cache.
     */
    public function clearAllCache(): void
    {
        try {
            Cache::tags([self::CACHE_TAG])->flush();
            Log::info('Order cache cleared successfully');
        } catch (\Exception $e) {
            Log::error('Failed to clear order cache', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Clear cache patterns related to statistics.
     */
    public function clearStatisticsCache(): void
    {
        try {
            $keys = [
                self::CACHE_PREFIX . 'statistics',
                self::CACHE_PREFIX . 'monthly_stats:*',
                self::CACHE_PREFIX . 'top_customers:*',
                self::CACHE_PREFIX . 'recent:*',
                self::CACHE_PREFIX . 'status:*',
            ];

            foreach ($keys as $key) {
                Cache::tags([self::CACHE_TAG])->forget($key);
            }

            Log::info('Order statistics cache cleared');
        } catch (\Exception $e) {
            Log::error('Failed to clear order statistics cache', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Warm up cache with important data.
     */
    public function warmUpCache(): void
    {
        try {
            // Cache recent orders
            $this->getRecentOrders();

            // Cache order statistics
            $this->getOrderStatistics();

            // Cache monthly statistics for current year
            $this->getMonthlyOrderStatistics();

            // Cache top customers
            $this->getTopCustomers();

            Log::info('Order cache warmed up successfully');
        } catch (\Exception $e) {
            Log::error('Failed to warm up order cache', ['error' => $e->getMessage()]);
        }
    }
}
