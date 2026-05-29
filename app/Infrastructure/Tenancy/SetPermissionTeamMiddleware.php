<?php

namespace App\Infrastructure\Tenancy;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tells Spatie Permission which "team" (agency) to scope all permission
 * checks to for the current request.  Must run after auth resolves the user.
 */
class SetPermissionTeamMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->agency_id) {
            setPermissionsTeamId($user->agency_id);
        }

        return $next($request);
    }
}
