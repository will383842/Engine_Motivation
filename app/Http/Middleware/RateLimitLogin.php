<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limit login attempts: 5 attempts per minute per IP.
 */
class RateLimitLogin
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_MINUTES = 1;

    public function __construct(
        private RateLimiter $limiter,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = 'login:' . $request->ip();

        if ($this->limiter->tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $retryAfter = $this->limiter->availableIn($key);
            abort(429, "Too many login attempts. Retry after {$retryAfter} seconds.");
        }

        $response = $next($request);

        // Only count failed attempts (non-redirect to dashboard = failed)
        if ($request->isMethod('POST') && $response->getStatusCode() !== 302) {
            $this->limiter->hit($key, self::DECAY_MINUTES * 60);
        }

        return $response;
    }
}
