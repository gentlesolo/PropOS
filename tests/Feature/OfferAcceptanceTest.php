<?php

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Offer;
use App\Infrastructure\Persistence\Models\Property;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\PipelineStage;
use App\Application\Offers\Actions\ProcessAcceptedOfferAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('accepting an offer automatically creates transaction, tasks, compliance docs, and updates deal stage', function () {
    // Run the seeder to populate roles and permissions
    $this->seed(Database\Seeders\RoleAndPermissionSeeder::class);

    $agency = Agency::factory()->create([
        'commission_splits' => ['agent' => 60.00, 'principal' => 30.00, 'referral' => 10.00]
    ]);

    $agent = User::factory()->create(['agency_id' => $agency->id]);
    $principal = User::factory()->create(['agency_id' => $agency->id]);
    
    // Assign principal role using Spatie Permission
    setPermissionsTeamId($agency->id);
    $principal->assignRole('principal');

    $contact = Contact::create([
        'agency_id' => $agency->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'type' => 'buyer',
        'status' => 'active',
    ]);

    $property = Property::create([
        'agency_id' => $agency->id,
        'address_line_1' => '123 Luxury Way',
        'city' => 'Lagos',
        'state_province' => 'Lagos',
        'country' => 'NG',
        'property_type' => 'house',
    ]);

    $listing = Listing::create([
        'agency_id' => $agency->id,
        'property_id' => $property->id,
        'agent_id' => $agent->id,
        'status' => 'active',
        'listing_price' => 1000000.00,
        'commission_rate' => 5.50,
        'mandate_type' => 'open',
        'mandate_start_date' => now()->subDays(5),
    ]);

    $stage = PipelineStage::create([
        'agency_id' => $agency->id,
        'name' => 'Under Negotiation',
        'pipeline_type' => 'sale',
        'order' => 1,
    ]);

    $wonStage = PipelineStage::create([
        'agency_id' => $agency->id,
        'name' => 'Offer Accepted',
        'pipeline_type' => 'sale',
        'order' => 2,
        'is_won' => true,
    ]);

    $deal = Deal::create([
        'agency_id' => $agency->id,
        'pipeline_stage_id' => $stage->id,
        'contact_id' => $contact->id,
        'listing_id' => $listing->id,
        'assigned_agent_id' => $agent->id,
        'title' => 'Deal for John Doe',
        'type' => 'sale',
        'value' => 1000000.00,
    ]);

    $offer = Offer::create([
        'agency_id' => $agency->id,
        'deal_id' => $deal->id,
        'listing_id' => $listing->id,
        'contact_id' => $contact->id,
        'submitted_by' => $agent->id,
        'amount' => 950000.00,
        'type' => 'sale',
        'status' => 'pending',
    ]);

    // Run the action
    $action = app(ProcessAcceptedOfferAction::class);
    $transaction = $action->execute($offer);

    // Assert Transaction was created
    expect($transaction)->not->toBeNull();
    expect($transaction->sale_price)->toEqual(950000.00);
    expect($transaction->commission_rate)->toEqual(5.50);
    expect($transaction->agent_split)->toEqual(60.00);
    expect($transaction->status)->toBe('fica_pending');

    // Assert Deal stage was updated
    $deal->refresh();
    expect($deal->pipeline_stage_id)->toBe($wonStage->id);

    // Assert FICA Documents were created
    expect($transaction->ficaDocuments()->count())->toBe(4);
    expect($transaction->documents()->count())->toBe(5);

    // Assert Tasks were created
    expect($transaction->tasks()->count())->toBe(4);
});
