<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class SecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Rate limiting
        $key = 'security:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 100)) {
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ]);

            abort(429, 'Demasiadas solicitudes. Intenta de nuevo más tarde.');
        }

        RateLimiter::hit($key, 3600); // 1 hour window

        // Security headers
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Content Security Policy
        $csp = "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://js.stripe.com; " .
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
            "font-src 'self' https://fonts.gstatic.com; " .
            "img-src 'self' data: https: blob:; " .
            "connect-src 'self' https://api.stripe.com; " .
            "frame-src https://js.stripe.com;";

        $response->headers->set('Content-Security-Policy', $csp);

        // Log suspicious activity
        $this->logSuspiciousActivity($request);

        return $response;
    }

    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity(Request $request): void
    {
        $suspiciousPatterns = [
            'script',
            'javascript:',
            '<script',
            'eval(',
            'document.cookie',
            'union select',
            'drop table',
            'insert into',
            'delete from',
            '../',
            '..\\',
        ];


        $payload = $request->getContent();
        $params  = json_encode($request->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $input = Str::of($payload . ' ' . $params)->lower()->toString();

        foreach ($suspiciousPatterns as $pattern) {
            if (strpos($input, $pattern) !== false) {
                Log::warning('Suspicious activity detected', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'url' => $request->fullUrl(),
                    'pattern' => $pattern,
                    'input' => substr($input, 0, 500),
                    'user_id' => auth()->id(),
                ]);
                break;
            }
        }

        // Log failed authentication attempts
        if ($request->is('login') && $request->isMethod('POST')) {
            $key = 'login_attempts:' . $request->ip();
            $attempts = RateLimiter::attempts($key);

            if ($attempts > 5) {
                Log::warning('Multiple failed login attempts', [
                    'ip' => $request->ip(),
                    'attempts' => $attempts,
                    'email' => $request->input('email'),
                ]);
            }
        }
    }
}
