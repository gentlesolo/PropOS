<?php

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\User;
use App\Http\Livewire\Marketing\FollowUpSequencesPage;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authorized users can view, create, pause, and cancel follow-up sequences', function () {
    $this->seed(Database\Seeders\RoleAndPermissionSeeder::class);

    $agency = Agency::factory()->create();
    $user = User::factory()->create(['agency_id' => $agency->id]);
    
    setPermissionsTeamId($agency->id);
    $user->assignRole('agent');

    $contact = Contact::create([
        'agency_id' => $agency->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
        'type' => 'buyer',
        'status' => 'new',
    ]);

    // Acting as the user, test the Livewire component
    $this->actingAs($user);

    Livewire::test(FollowUpSequencesPage::class)
        ->set('contact_id', $contact->id)
        ->set('name', 'Test Sequence')
        ->set('steps', [
            [
                'type' => 'email',
                'subject' => 'Introduction',
                'message_template' => 'Hi {first_name}, glad to connect.',
                'delay_days' => 1,
            ]
        ])
        ->call('createSequence')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $this->assertDatabaseHas('follow_up_sequences', [
        'contact_id' => $contact->id,
        'name' => 'Test Sequence',
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('follow_up_steps', [
        'type' => 'email',
        'subject' => 'Introduction',
        'message_template' => 'Hi {first_name}, glad to connect.',
    ]);
});
