<?php
declare(strict_types=1);
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Twilio\Security\RequestValidator;
use Symfony\Component\HttpFoundation\Response;
class VerifyTwilioSignature {
    public function handle(Request $request, Closure $next): Response {
        $validator = new RequestValidator(config("whatsapp.auth_token"));
        $url = $request->fullUrl();
        $params = $request->all();
        $signature = $request->header("X-Twilio-Signature", "");
        if (!$validator->validate($signature, $url, $params)) { abort(403, "Invalid Twilio signature"); }
        return $next($request);
    }
}