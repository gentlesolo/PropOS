<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset static TenantResolver state
        if ($this->app->bound(\App\Infrastructure\Tenancy\TenantResolver::class)) {
            $this->app->make(\App\Infrastructure\Tenancy\TenantResolver::class)->setCurrentAgency(null);
        }

        // Reset Spatie permissions team ID
        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId(null);
        }
    }
}
