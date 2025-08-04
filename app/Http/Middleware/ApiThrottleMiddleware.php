<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiThrottleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $limits = 'default'): Response
    {
        $identifier = $this->getIdentifier($request);
        $limitsConfig = $this->getLimitsConfig($limits);

        // Check each limit tier
        foreach ($limitsConfig as $tier => $config) {
            $key = "api_throttle:{$tier}:{$identifier}";
            $maxAttempts = $config['max_attempts'];
            $decayMinutes = $config['decay_minutes'];

            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $this->logThrottleEvent($request, $identifier, $tier, $maxAttempts);
                
                $retryAfter = RateLimiter::availableIn($key);
                
                return response()->json([
                    'error' => 'Too Many Requests',
                    'message' => "Rate limit exceeded for {$tier} tier. Try again in {$retryAfter} seconds.",
                    'retry_after' => $retryAfter,
                    'limit' => $maxAttempts,
                    'window' => $decayMinutes * 60,
                ], 429)->header('Retry-After', $retryAfter);
            }

            // Hit the rate limiter
            RateLimiter::hit($key, $decayMinutes * 60);
        }

        // Add rate limit headers to response
        $response = $next($request);
        
        return $this->addRateLimitHeaders($response, $identifier, $limitsConfig);
    }

    /**
     * Get unique identifier for rate limiting.
     */
    private function getIdentifier(Request $request): string
    {
        // Use user ID if authenticated
        if (Auth::check()) {
            return 'user:' . Auth::id();
        }

        // Use API key if present
        if ($apiKey = $request->header('X-API-Key')) {
            return 'api_key:' . hash('sha256', $apiKey);
        }

        // Fall back to IP address
        return 'ip:' . $request->ip();
    }

    /**
     * Get rate limits configuration.
     */
    private function getLimitsConfig(string $limits): array
    {
        $configs = [
            'default' => [
                'minute' => ['max_attempts' => 60, 'decay_minutes' => 1],
                'hour' => ['max_attempts' => 1000, 'decay_minutes' => 60],
                'day' => ['max_attempts' => 10000, 'decay_minutes' => 1440],
            ],
            'strict' => [
                'minute' => ['max_attempts' => 30, 'decay_minutes' => 1],
                'hour' => ['max_attempts' => 500, 'decay_minutes' => 60],
                'day' => ['max_attempts' => 2000, 'decay_minutes' => 1440],
            ],
            'lenient' => [
                'minute' => ['max_attempts' => 120, 'decay_minutes' => 1],
                'hour' => ['max_attempts' => 2000, 'decay_minutes' => 60],
                'day' => ['max_attempts' => 20000, 'decay_minutes' => 1440],
            ],
            'auth' => [
                'minute' => ['max_attempts' => 5, 'decay_minutes' => 1],
                'hour' => ['max_attempts' => 20, 'decay_minutes' => 60],
                'day' => ['max_attempts' => 100, 'decay_minutes' => 1440],
            ],
            'payment' => [
                'minute' => ['max_attempts' => 10, 'decay_minutes' => 1],
                'hour' => ['max_attempts' => 50, 'decay_minutes' => 60],
                'day' => ['max_attempts' => 200, 'decay_minutes' => 1440],
            ],
        ];

        return $configs[$limits] ?? $configs['default'];
    }

    /**
     * Add rate limit headers to response.
     */
    private function addRateLimitHeaders(Response $response, string $identifier, array $limitsConfig): Response
    {
        // Add headers for the most restrictive limit (minute)
        $minuteConfig = $limitsConfig['minute'];
        $key = "api_throttle:minute:{$identifier}";
        
        $maxAttempts = $minuteConfig['max_attempts'];
        $remainingAttempts = $maxAttempts - RateLimiter::attempts($key);
        $resetTime = now()->addMinutes($minuteConfig['decay_minutes'])->timestamp;

        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remainingAttempts));
        $response->headers->set('X-RateLimit-Reset', $resetTime);

        return $response;
    }

    /**
     * Log throttle events.
     */
    private function logThrottleEvent(Request $request, string $identifier, string $tier, int $maxAttempts): void
    {
        Log::warning('API rate limit exceeded', [
            'identifier' => $identifier,
            'tier' => $tier,
            'max_attempts' => $maxAttempts,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => Auth::id(),
        ]);

        // Track repeated offenders
        $offenderKey = "api_offender:{$identifier}";
        $offenses = Cache::increment($offenderKey, 1);
        Cache::expire($offenderKey, 3600); // 1 hour

        if ($offenses >= 5) {
            Log::alert('Repeated API rate limit violations', [
                'identifier' => $identifier,
                'offenses' => $offenses,
                'ip' => $request->ip(),
                'user_id' => Auth::id(),
            ]);

            // Optionally, implement temporary bans here
            $this->implementTemporaryBan($identifier, $offenses);
        }
    }

    /**
     * Implement temporary ban for repeated offenders.
     */
    private function implementTemporaryBan(string $identifier, int $offenses): void
    {
        // Progressive ban duration
        $banDurations = [
            5 => 300,   // 5 minutes for 5 offenses
            10 => 900,  // 15 minutes for 10 offenses
            20 => 3600, // 1 hour for 20 offenses
            50 => 86400, // 24 hours for 50 offenses
        ];

        $banDuration = 300; // Default 5 minutes
        foreach ($banDurations as $threshold => $duration) {
            if ($offenses >= $threshold) {
                $banDuration = $duration;
            }
        }

        $banKey = "api_ban:{$identifier}";
        Cache::put($banKey, true, $banDuration);

        Log::warning('Temporary API ban implemented', [
            'identifier' => $identifier,
            'duration' => $banDuration,
            'offenses' => $offenses,
        ]);
    }

    /**
     * Check if identifier is temporarily banned.
     */
    public static function isBanned(string $identifier): bool
    {
        return Cache::has("api_ban:{$identifier}");
    }

    /**
     * Get ban expiry time.
     */
    public static function getBanExpiry(string $identifier): ?int
    {
        $banKey = "api_ban:{$identifier}";
        
        if (Cache::has($banKey)) {
            // This is an approximation since Redis doesn't directly expose TTL in Laravel Cache
            return now()->addMinutes(5)->timestamp; // Minimum ban duration
        }

        return null;
    }
}

