<?php
declare(strict_types=1);
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class AdminIpWhitelist {
    public function handle(Request $request, Closure $next): Response {
        $whitelist = config("services.admin_ip_whitelist", []);
        if (!empty($whitelist) && !in_array($request->ip(), $whitelist)) { abort(403, "IP not allowed"); }
        return $next($request);
    }
}