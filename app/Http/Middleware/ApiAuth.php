<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return response()->json([
                'error'   => 'unauthorized',
                'message' => 'Missing API token. Send it as `Authorization: Bearer <token>` or `?api_key=<token>`.',
            ], 401);
        }

        $key = ApiKey::findByPlainToken($token);
        if (!$key) {
            return response()->json([
                'error'   => 'unauthorized',
                'message' => 'Invalid or revoked API token.',
            ], 401);
        }

        // Touch last_used_at without bumping updated_at every request more than once/min
        if (!$key->last_used_at || $key->last_used_at->lt(now()->subMinute())) {
            $key->forceFill(['last_used_at' => now()])->save();
        }

        $request->attributes->set('api_key', $key);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        if (is_string($header) && stripos($header, 'Bearer ') === 0) {
            return trim(substr($header, 7));
        }
        return $request->query('api_key') ?: $request->input('api_key');
    }
}
