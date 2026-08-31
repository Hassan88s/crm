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
        $response->headers->set('X-Force-Json', 'v3');
        // Some layers in Laravel/Symfony bump JSON responses to 406 when the
        // request-time Accept did not match a served type. If our JSON body
        // decoded cleanly and status is 406, restore 200.
        if ($response->getStatusCode() === 406) {
            $body = (string) $response->getContent();
            if ($body !== '' && json_decode($body) !== null && json_last_error() === JSON_ERROR_NONE) {
                $response->setStatusCode(200);
                $response->headers->set('X-Force-Json', 'v3-coerced-406-to-200');
            }
        }
        return $response;
    }
}
