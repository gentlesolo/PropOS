<?php

namespace App\Infrastructure\Tenancy;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    protected TenantResolver $resolver;

    public function __construct(TenantResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $agency = $this->resolver->resolve($request);

        if ($agency) {
            // Set Spatie Permission team context dynamically
            setPermissionsTeamId($agency->id);
        }

        return $next($request);
    }
}
