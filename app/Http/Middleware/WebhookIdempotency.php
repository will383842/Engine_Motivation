<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class WebhookIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-Idempotency-Key');

        // If no idempotency key provided, generate one from the body hash
        if (!$key) {
            $key = hash('sha256', $request->getContent());
        }

        $cacheKey = 'webhook:seen:' . $key;

        if (Cache::has($cacheKey)) {
            return response()->json(['message' => 'Already processed'], 200);
        }

        Cache::put($cacheKey, true, 86400); // TTL 24h

        return $next($request);
    }
}
