<?php

namespace App\Http\Middleware;

use App\Infrastructure\Persistence\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatePublicApiKey
{
    /**
     * @param string|null $requiredType  Pass 'full_access' to reject public_read keys.
     */
    public function handle(Request $request, Closure $next, ?string $requiredType = null): Response
    {
        $token = $request->bearerToken() ?? $request->query('api_key');

        if (! $token) {
            return response()->json(['error' => 'API key required.'], 401);
        }

        $key = ApiKey::where('token', $token)->first();

        if (! $key || $key->isExpired()) {
            return response()->json(['error' => 'Invalid or expired API key.'], 401);
        }

        if ($requiredType && $key->type !== $requiredType) {
            return response()->json([
                'error' => "This endpoint requires a '{$requiredType}' API key.",
            ], 403);
        }

        $key->updateQuietly(['last_used_at' => now()]);

        $request->attributes->set('api_key', $key);
        $request->attributes->set('agency_id', $key->agency_id);

        return $next($request);
    }
}
