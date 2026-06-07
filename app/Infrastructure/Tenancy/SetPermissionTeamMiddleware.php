<?php

namespace App\Infrastructure\Tenancy;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs on every web request (including Livewire AJAX via the web middleware
 * group).  Resolves the current tenant so BelongsToAgencyScope always has a
 * valid agency_id, then tells Spatie Permission which team to scope to.
 */
class SetPermissionTeamMiddleware
{
    public function __construct(private TenantResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Resolve tenant on every web request — this covers Livewire AJAX calls
        // that bypass route-level TenantMiddleware.
        $agency = $this->resolver->resolve($request);

        if ($agency) {
            setPermissionsTeamId($agency->id);
        }

        return $next($request);
    }
}
