<?php

namespace App\Infrastructure\Tenancy;

use App\Infrastructure\Persistence\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TenantResolver
{
    protected static ?Agency $currentAgency = null;

    public function resolve(Request $request): ?Agency
    {
        if (config('tenancy.mode') === 'self_hosted') {
            $agency = Agency::first() ?? Agency::factory()->create(['id' => 1, 'slug' => 'default']);
            static::$currentAgency = $agency;
            return $agency;
        }

        // SaaS mode: resolve from authenticated user
        $user = $request->user() ?? auth()->user();
        if ($user) {
            static::$currentAgency = $user->agency;
            return static::$currentAgency;
        }

        static::$currentAgency = null;
        return null;
    }

    public function setCurrentAgency(?Agency $agency): void
    {
        static::$currentAgency = $agency;
    }

    public function getCurrentAgency(): ?Agency
    {
        return static::$currentAgency;
    }

    public function getCurrentAgencyId(): ?int
    {
        return static::$currentAgency?->id;
    }
}
