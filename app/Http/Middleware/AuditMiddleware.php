<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Capture request data
        $auditData = [
            'user_id' => Auth::id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route_name' => $request->route()?->getName(),
            'controller_action' => $this->getControllerAction($request),
            'request_data' => $this->sanitizeRequestData($request),
            'session_id' => $request->session()?->getId(),
            'referer' => $request->header('referer'),
            'started_at' => now(),
        ];

        // Process the request
        $response = $next($request);

        // Calculate performance metrics
        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $auditData = array_merge($auditData, [
            'response_status' => $response->getStatusCode(),
            'response_size' => strlen($response->getContent()),
            'execution_time' => round(($endTime - $startTime) * 1000, 2), // milliseconds
            'memory_usage' => $endMemory - $startMemory,
            'peak_memory' => memory_get_peak_usage(),
            'completed_at' => now(),
        ]);

        // Log the audit data
        $this->logAuditData($auditData, $response);

        // Store in database for important actions
        if ($this->shouldStoreInDatabase($request, $response)) {
            $this->storeAuditData($auditData);
        }

        return $response;
    }

    /**
     * Get controller and action from request.
     */
    private function getControllerAction(Request $request): ?string
    {
        $route = $request->route();

        if (!$route) {
            return null;
        }

        $action = $route->getAction();

        if (isset($action['controller'])) {
            return $action['controller'];
        }

        return null;
    }

    /**
     * Sanitize request data for logging.
     */
    private function sanitizeRequestData(Request $request): array
    {
        $data = $request->all();

        // Remove sensitive fields
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            'api_key',
            'secret',
            'credit_card',
            'cvv',
            'ssn',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        // Limit data size
        $jsonData = json_encode($data);
        if (strlen($jsonData) > 10000) { // 10KB limit
            return ['message' => 'Request data too large to log'];
        }

        return $data;
    }

    /**
     * Log audit data.
     */
    private function logAuditData(array $auditData, Response $response): void
    {
        $logLevel = $this->getLogLevel($response->getStatusCode());
        $message = $this->formatLogMessage($auditData);

        Log::log($logLevel, $message, [
            'audit' => $auditData,
            'context' => 'http_request',
        ]);

        // Log slow requests
        if ($auditData['execution_time'] > 1000) { // > 1 second
            Log::warning('Slow request detected', [
                'url' => $auditData['url'],
                'execution_time' => $auditData['execution_time'],
                'user_id' => $auditData['user_id'],
                'memory_usage' => $auditData['memory_usage'],
            ]);
        }

        // Log high memory usage
        if ($auditData['memory_usage'] > 50 * 1024 * 1024) { // > 50MB
            Log::warning('High memory usage detected', [
                'url' => $auditData['url'],
                'memory_usage' => $auditData['memory_usage'],
                'peak_memory' => $auditData['peak_memory'],
                'user_id' => $auditData['user_id'],
            ]);
        }
    }

    /**
     * Get appropriate log level based on response status.
     */
    private function getLogLevel(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        } elseif ($statusCode >= 400) {
            return 'warning';
        } elseif ($statusCode >= 300) {
            return 'info';
        } else {
            return 'info';
        }
    }

    /**
     * Format log message.
     */
    private function formatLogMessage(array $auditData): string
    {
        $user = $auditData['user_id'] ? "User {$auditData['user_id']}" : 'Guest';
        $method = $auditData['method'];
        $url = $auditData['url'];
        $status = $auditData['response_status'];
        $time = $auditData['execution_time'];

        return "{$user} {$method} {$url} - {$status} ({$time}ms)";
    }

    /**
     * Determine if audit data should be stored in database.
     */
    private function shouldStoreInDatabase(Request $request, Response $response): bool
    {
        // Store for authenticated users
        if (Auth::check()) {
            return true;
        }

        // Store for failed requests
        if ($response->getStatusCode() >= 400) {
            return true;
        }

        // Store for important routes
        $importantRoutes = [
            'login',
            'register',
            'password.reset',
            'verification.verify',
            'orders.store',
            'payments.confirm',
        ];

        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, $importantRoutes)) {
            return true;
        }

        // Store for admin/support actions
        if (str_contains($request->path(), 'admin/') || str_contains($request->path(), 'support/')) {
            return true;
        }

        return false;
    }

    /**
     * Store audit data in database.
     */
    private function storeAuditData(array $auditData): void
    {
        try {
            DB::table('audit_logs')->insert([
                'user_id' => $auditData['user_id'],
                'ip_address' => $auditData['ip_address'],
                'user_agent' => $auditData['user_agent'],
                'method' => $auditData['method'],
                'url' => $auditData['url'],
                'route_name' => $auditData['route_name'],
                'controller_action' => $auditData['controller_action'],
                'request_data' => json_encode($auditData['request_data']),
                'response_status' => $auditData['response_status'],
                'response_size' => $auditData['response_size'],
                'execution_time' => $auditData['execution_time'],
                'memory_usage' => $auditData['memory_usage'],
                'session_id' => $auditData['session_id'],
                'referer' => $auditData['referer'],
                'created_at' => $auditData['started_at'],
                'updated_at' => $auditData['completed_at'],
            ]);
        } catch (\Exception $e) {
            // Don't fail the request if audit logging fails
            Log::error('Failed to store audit data', [
                'error' => $e->getMessage(),
                'audit_data' => $auditData,
            ]);
        }
    }
}
