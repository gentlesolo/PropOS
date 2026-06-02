<?php

namespace App\Http\Middleware;

use App\Infrastructure\Persistence\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatePublicApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->query('api_key');

        if (! $token) {
            return response()->json(['error' => 'API key required.'], 401);
        }

        $key = ApiKey::where('token', $token)->first();

        if (! $key || $key->isExpired()) {
            return response()->json(['error' => 'Invalid or expired API key.'], 401);
        }

        $key->updateQuietly(['last_used_at' => now()]);

        $request->attributes->set('api_key', $key);
        $request->attributes->set('agency_id', $key->agency_id);

        return $next($request);
    }
}
