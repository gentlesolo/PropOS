<?php

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Tenancy\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Register a test route that uses the tenant middleware
    Route::get('/test-tenant', function () {
        $resolver = app(TenantResolver::class);
        return response()->json([
            'resolved' => $resolver->getCurrentAgencyId(),
            'user' => auth()->user()?->id,
        ]);
    })->middleware(['web', 'tenant']);
});

test('authenticated user resolves their agency as active tenant', function () {
    config(['tenancy.mode' => 'saas']);
    $agency = Agency::factory()->create();
    $user = User::factory()->create(['agency_id' => $agency->id]);

    $response = $this->actingAs($user)->get('/test-tenant');

    $response->assertOk();
    $response->assertJsonFragment([
        'resolved' => $agency->id,
        'user' => $user->id,
    ]);
});

test('guest request does not resolve active tenant in saas mode', function () {
    config(['tenancy.mode' => 'saas']);

    $response = $this->get('/test-tenant');

    $response->assertOk();
    $response->assertJsonFragment([
        'resolved' => null,
        'user' => null,
    ]);
});

test('database queries are automatically scoped by resolved tenant', function () {
    $agency1 = Agency::factory()->create();
    $agency2 = Agency::factory()->create();

    // Create users under each agency
    $user1 = User::factory()->create(['agency_id' => $agency1->id]);
    $user2 = User::factory()->create(['agency_id' => $agency2->id]);

    // Set context as agency 1
    $resolver = app(TenantResolver::class);
    $resolver->setCurrentAgency($agency1);

    // Enforce that only User 1 is retrieved
    $users = User::all();
    expect($users->count())->toBe(1);
    expect($users->first()->id)->toBe($user1->id);

    // Set context as agency 2
    $resolver->setCurrentAgency($agency2);

    $users = User::all();
    expect($users->count())->toBe(1);
    expect($users->first()->id)->toBe($user2->id);
});
