<?php

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Property;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\OpenHouse;
use App\Infrastructure\Persistence\Models\OpenHouseRsvp;
use App\Http\Livewire\Listing\ListingDetailPage;
use App\Http\Livewire\Listing\PublicPocketListingPage;
use App\Http\Livewire\Viewing\PublicOpenHouseRsvpPage;
use App\Http\Livewire\Viewing\OpenHousePage;
use App\Http\Livewire\Settings\ProfilePage;
use App\Application\Listing\Services\MlsSyncService;
use App\Infrastructure\Queue\Jobs\SyncMlsListingsJob;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(Database\Seeders\RoleAndPermissionSeeder::class);
    $this->agency = Agency::factory()->create();
    $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
    setPermissionsTeamId($this->agency->id);
    $this->user->assignRole('agent');
    
    $this->property = Property::create([
        'agency_id' => $this->agency->id,
        'address_line_1' => '123 Test Street',
        'city' => 'Lagos',
        'state_province' => 'Lagos',
        'country' => 'NG',
        'property_type' => 'house',
    ]);

    $this->listing = Listing::create([
        'agency_id' => $this->agency->id,
        'property_id' => $this->property->id,
        'agent_id' => $this->user->id,
        'listing_price' => 500000,
        'status' => 'draft',
        'mandate_type' => 'sole',
        'mandate_start_date' => now(),
    ]);
});

test('agents can configure virtual tours on the listing detail page', function () {
    $this->actingAs($this->user);

    Livewire::test(ListingDetailPage::class, ['listing' => $this->listing])
        ->set('virtual_tour_type', 'youtube')
        ->set('virtual_tour_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->call('saveListing')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('listings', [
        'id' => $this->listing->id,
        'virtual_tour_type' => 'youtube',
        'virtual_tour_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    ]);
});

test('agents can toggle pocket listing status and generate tokens', function () {
    $this->actingAs($this->user);

    $this->assertNull($this->listing->pocket_token);
    $this->assertFalse((bool)$this->listing->is_pocket);

    Livewire::test(ListingDetailPage::class, ['listing' => $this->listing])
        ->call('togglePocketListing')
        ->assertHasNoErrors()
        ->assertSet('is_pocket', true)
        ->assertNotSet('pocket_token', '');

    $this->listing->refresh();
    $this->assertTrue((bool)$this->listing->is_pocket);
    $this->assertNotNull($this->listing->pocket_token);

    // Verify public pocket page accessibility using the token
    Livewire::test(PublicPocketListingPage::class, ['token' => $this->listing->pocket_token])
        ->assertStatus(200)
        ->assertSee('Private Pocket Listing')
        ->assertSee('123 Test Street');
});

test('public users can rsvp to open houses and get enrolled in follow-ups', function () {
    $openHouse = OpenHouse::create([
        'agency_id' => $this->agency->id,
        'listing_id' => $this->listing->id,
        'agent_id' => $this->user->id,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHours(2),
        'status' => 'scheduled',
        'rsvp_slug' => 'test-open-house',
    ]);

    Livewire::test(PublicOpenHouseRsvpPage::class, ['rsvp_slug' => 'test-open-house'])
        ->set('name', 'John Doe')
        ->set('email', 'johndoe@example.com')
        ->set('phone', '1234567890')
        ->call('submitRsvp')
        ->assertHasNoErrors()
        ->assertSet('registered', true);

    // Contact should be automatically created in database
    $contact = Contact::where('email', 'johndoe@example.com')->first();
    $this->assertNotNull($contact);

    // RSVP should be registered
    $this->assertDatabaseHas('open_house_rsvps', [
        'open_house_id' => $openHouse->id,
        'contact_id' => $contact->id,
        'guest_name' => 'John Doe',
        'checked_in' => false,
    ]);

    // Follow-up sequence should be initialized
    $this->assertDatabaseHas('follow_up_sequences', [
        'contact_id' => $contact->id,
        'name' => 'Open House RSVP (123 Test Street)',
    ]);
});

test('open house check-in auto-enrolls guests in follow-up sequences', function () {
    $this->actingAs($this->user);

    $openHouse = OpenHouse::create([
        'agency_id' => $this->agency->id,
        'listing_id' => $this->listing->id,
        'agent_id' => $this->user->id,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHours(2),
        'status' => 'live',
        'rsvp_slug' => 'test-open-house-live',
    ]);

    Livewire::test(OpenHousePage::class)
        ->set('checkingInId', $openHouse->id)
        ->set('checkin_name', 'Jane Guest')
        ->set('checkin_email', 'jane.guest@example.com')
        ->call('checkIn')
        ->assertHasNoErrors();

    $contact = Contact::where('email', 'jane.guest@example.com')->first();
    $this->assertNotNull($contact);

    $this->assertDatabaseHas('open_house_rsvps', [
        'open_house_id' => $openHouse->id,
        'contact_id' => $contact->id,
        'checked_in' => true,
    ]);

    $this->assertDatabaseHas('follow_up_sequences', [
        'contact_id' => $contact->id,
        'name' => 'Open House Follow-up (123 Test Street)',
    ]);
});

test('mls sync updates listing price and status simulation', function () {
    $this->listing->update(['mls_id' => 'MLS-SOLD-1']);

    $service = new MlsSyncService();
    $result = $service->syncListing($this->listing);

    $this->assertTrue($result['success']);
    $this->assertTrue($result['updated']);
    $this->assertEquals('sold', $result['changes']['status']['new']);

    $this->listing->refresh();
    $this->assertEquals('sold', $this->listing->status);
    $this->assertNotNull($this->listing->mls_last_synced_at);
});

test('manual mls sync can be triggered from listing detail page', function () {
    $this->actingAs($this->user);
    $this->listing->update(['mls_id' => 'MLS-PRICE_DROP-1']);

    Livewire::test(ListingDetailPage::class, ['listing' => $this->listing])
        ->call('syncWithMls')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $this->listing->refresh();
    $this->assertEquals(700000, $this->listing->listing_price); // 750000 - 50000 price offset
});

test('background mls sync job can be dispatched from settings page', function () {
    $this->actingAs($this->user);
    Queue::fake();

    Livewire::test(ProfilePage::class)
        ->call('runMlsSyncJob')
        ->assertHasNoErrors();

    Queue::assertPushed(SyncMlsListingsJob::class);
});
