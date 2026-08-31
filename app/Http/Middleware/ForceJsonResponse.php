<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force every request on the API prefix to be treated as JSON so Laravel's
 * content negotiation never returns 406 to a client that forgot the Accept
 * header (agents, curl one-liners, etc.).
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $response = $next($request);
        if (!$response->headers->has('Content-Type')) {
            $response->headers->set('Content-Type', 'application/json');
        }
        // Marker so we can confirm this middleware fired on the live server.
        $response->headers->set('X-Force-Json', 'v4');
        // Aggressively pin the response status via a header PHP will re-emit
        // to Apache/PHP-FPM. Some cPanel Apache setups rewrite the status
        // line based on content negotiation AFTER PHP hands off the response;
        // the Status header re-declares it so mod_fcgid/mod_proxy_fcgi keeps
        // whatever PHP set.
        $status = $response->getStatusCode();
        $body   = (string) $response->getContent();
        $bodyIsJson = $body !== '' && json_decode($body) !== null && json_last_error() === JSON_ERROR_NONE;
        // If the body decoded cleanly and the status is a client-error 4xx we
        // did not choose (Laravel controllers here only ever return 200/201/
        // 400/401/404 explicitly), coerce back to 200.
        if ($bodyIsJson && $status === 406) {
            $status = 200;
            $response->setStatusCode(200);
            $response->headers->set('X-Force-Json', 'v4-coerced-406');
        }
        $response->headers->set('Status', $status . ' ' . ($response::$statusTexts[$status] ?? 'OK'));
        return $response;
    }
}
