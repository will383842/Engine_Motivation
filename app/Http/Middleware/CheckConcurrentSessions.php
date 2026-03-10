<?php
declare(strict_types=1);
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
class CheckConcurrentSessions {
    public function handle(Request $request, Closure $next): Response {
        $user = $request->user();
        if (!$user) { return $next($request); }
        $key = "admin_sessions:{$user->id}";
        $sessions = Cache::get($key, []);
        $sessions[session()->getId()] = now()->timestamp;
        $sessions = array_filter($sessions, fn($t) => now()->timestamp - $t < 7200);
        if (count($sessions) > 2) { array_shift($sessions); }
        Cache::put($key, $sessions, 7200);
        return $next($request);
    }
}