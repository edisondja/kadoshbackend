<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Rate-limit solo para /api/* (rutas en web.php).
 * Buckets separados: login (estricto) vs API general.
 */
class ThrottleApiRequests
{
    /** @var RateLimiter */
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle($request, Closure $next)
    {
        $path = ltrim($request->path(), '/');

        if (strpos($path, 'api/') !== 0 && $path !== 'api') {
            return $next($request);
        }

        $ip = $request->ip() ?: 'unknown';
        $host = $request->getHost();

        // Login / admin login: 10 por minuto (anti fuerza bruta / spam)
        if (preg_match('#^api/login(/|$)#', $path) || $path === 'api/admin/login') {
            return $this->throttle($request, $next, 'api_login:' . $host . '|' . $ip, 10, 1);
        }

        // API general: 120 por minuto por IP
        return $this->throttle($request, $next, 'api_general:' . $host . '|' . $ip, 120, 1);
    }

    private function throttle($request, Closure $next, $key, $maxAttempts, $decayMinutes)
    {
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);
            throw new HttpException(429, 'Too Many Attempts.', null, [
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        if (method_exists($response, 'header')) {
            $remaining = $this->limiter->retriesLeft($key, $maxAttempts);
            $response->headers->set('X-RateLimit-Limit', $maxAttempts);
            $response->headers->set('X-RateLimit-Remaining', max(0, $remaining));
        }

        return $response;
    }
}
