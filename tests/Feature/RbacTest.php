<?php

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Run the seeder to populate roles and permissions
    $this->seed(RoleAndPermissionSeeder::class);

    // Register a test route protected by permission middleware
    Route::get('/test-permission', function () {
        return response()->json(['status' => 'authorized']);
    })->middleware(['web', 'can:agency.manage']);
});

test('roles are assigned default permissions correctly', function () {
    $agency = Agency::factory()->create();
    setPermissionsTeamId($agency->id);

    $agentUser = User::factory()->create(['agency_id' => $agency->id]);
    $agentUser->assignRole('agent');

    $principalUser = User::factory()->create(['agency_id' => $agency->id]);
    $principalUser->assignRole('principal');

    // Agent should have listings.view_own, but not agency.manage
    expect($agentUser->hasPermissionTo('listings.view_own'))->toBeTrue();
    expect($agentUser->hasPermissionTo('agency.manage'))->toBeFalse();

    // Principal should have all permissions
    expect($principalUser->hasPermissionTo('listings.view_own'))->toBeTrue();
    expect($principalUser->hasPermissionTo('agency.manage'))->toBeTrue();
});

test('unauthorized users are blocked from protected routes', function () {
    $agency = Agency::factory()->create();
    setPermissionsTeamId($agency->id);

    $agentUser = User::factory()->create(['agency_id' => $agency->id]);
    $agentUser->assignRole('agent');

    // Route requires 'agency.manage' permission
    $response = $this->actingAs($agentUser)
        ->get('/test-permission');

    $response->assertStatus(403);
});

test('authorized users can access protected routes', function () {
    $agency = Agency::factory()->create();
    setPermissionsTeamId($agency->id);

    $principalUser = User::factory()->create(['agency_id' => $agency->id]);
    $principalUser->assignRole('principal');

    // Principal has 'agency.manage' permission
    $response = $this->actingAs($principalUser)
        ->get('/test-permission');

    $response->assertOk();
    $response->assertJson(['status' => 'authorized']);
});
