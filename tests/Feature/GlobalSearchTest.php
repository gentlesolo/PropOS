<?php

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Property;
use App\Http\Livewire\Shared\GlobalSearch;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('global search correctly retrieves matching records', function () {
    $this->seed(Database\Seeders\RoleAndPermissionSeeder::class);

    $agency = Agency::factory()->create();
    $user = User::factory()->create(['agency_id' => $agency->id]);

    setPermissionsTeamId($agency->id);
    $user->assignRole('agent');

    $contact = Contact::create([
        'agency_id' => $agency->id,
        'first_name' => 'Alice',
        'last_name' => 'Wonderland',
        'email' => 'alice@example.com',
        'type' => 'buyer',
        'status' => 'new',
    ]);

    $property = Property::create([
        'agency_id' => $agency->id,
        'address_line_1' => '999 Wonderland St',
        'city' => 'Fantasy',
        'state_province' => 'Dream',
        'country' => 'US',
        'property_type' => 'house',
    ]);

    $listing = Listing::create([
        'agency_id' => $agency->id,
        'property_id' => $property->id,
        'agent_id' => $user->id,
        'status' => 'active',
        'listing_price' => 1200000.00,
        'commission_rate' => 5.00,
        'mandate_type' => 'open',
        'mandate_start_date' => now()->subDays(2),
    ]);

    $this->actingAs($user);

    Livewire::test(GlobalSearch::class)
        ->call('toggle')
        ->assertSet('isOpen', true)
        ->set('query', 'Alice')
        ->assertCount('results', 1)
        ->assertSee('Alice Wonderland')
        ->set('query', 'Wonderland')
        ->assertCount('results', 2); // Matches both contact name and property address
});
