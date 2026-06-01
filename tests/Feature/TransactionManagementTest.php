<?php

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Property;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\PipelineStage;
use App\Infrastructure\Persistence\Models\StageChecklistItem;
use App\Infrastructure\Persistence\Models\Contract;
use App\Infrastructure\Persistence\Models\CommissionSplitConfig;
use App\Infrastructure\Persistence\Models\Commission;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(Database\Seeders\RoleAndPermissionSeeder::class);
    $this->agency = Agency::factory()->create();
    $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
    setPermissionsTeamId($this->agency->id);
    $this->user->assignRole('principal'); // Full access permissions

    $this->property = Property::create([
        'agency_id' => $this->agency->id,
        'address_line_1' => '456 Automation Ave',
        'city' => 'Lagos',
        'state_province' => 'Lagos',
        'country' => 'NG',
        'property_type' => 'house',
    ]);

    $this->listing = Listing::create([
        'agency_id' => $this->agency->id,
        'agent_id' => $this->user->id,
        'property_id' => $this->property->id,
        'listing_type' => 'sale',
        'mandate_type' => 'sole',
        'status' => 'active',
        'listing_price' => 120000000.00,
        'mandate_start_date' => now()->toDateString(),
    ]);

    $this->contact = Contact::create([
        'agency_id' => $this->agency->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane.doe@example.com',
        'type' => 'buyer',
    ]);

    $this->stageInquiry = PipelineStage::create([
        'agency_id' => $this->agency->id,
        'name' => 'Lead Inquiry',
        'pipeline_type' => 'sale',
        'order' => 1,
    ]);

    $this->stageContract = PipelineStage::create([
        'agency_id' => $this->agency->id,
        'name' => 'Under Contract',
        'pipeline_type' => 'sale',
        'order' => 2,
    ]);

    $this->stageClosed = PipelineStage::create([
        'agency_id' => $this->agency->id,
        'name' => 'Closed Deal',
        'pipeline_type' => 'sale',
        'order' => 3,
        'is_won' => true,
    ]);
});

it('allows customized pipeline stages to be configured', function () {
    $this->actingAs($this->user);

    Livewire::test(App\Http\Livewire\Settings\PipelineStagesPage::class)
        ->assertSee('Lead Inquiry')
        ->set('name', 'Viewing Scheduled')
        ->set('pipeline_type', 'sale')
        ->set('order', 4)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('pipeline_stages', [
        'name' => 'Viewing Scheduled',
        'pipeline_type' => 'sale',
        'order' => 4,
    ]);
});

it('triggers automated checklist items on stage changes', function () {
    $this->actingAs($this->user);

    $deal = Deal::create([
        'agency_id' => $this->agency->id,
        'assigned_agent_id' => $this->user->id,
        'contact_id' => $this->contact->id,
        'listing_id' => $this->listing->id,
        'title' => 'Jane Purchase',
        'type' => 'sale',
        'value' => 120000000.00,
        'pipeline_stage_id' => $this->stageInquiry->id,
    ]);

    // Move deal to "Under Contract" stage
    Livewire::test(App\Http\Livewire\Crm\PipelineBoard::class)
        ->call('updateDealStage', $deal->id, $this->stageContract->id);

    $this->assertDatabaseHas('stage_checklist_items', [
        'deal_id' => $deal->id,
        'pipeline_stage_id' => $this->stageContract->id,
        'title' => 'Order home inspection',
    ]);
    $this->assertDatabaseHas('stage_checklist_items', [
        'deal_id' => $deal->id,
        'pipeline_stage_id' => $this->stageContract->id,
        'title' => 'Send disclosures to all parties',
    ]);
});

it('substitutes template variables during contract generation', function () {
    $this->actingAs($this->user);

    $deal = Deal::create([
        'agency_id' => $this->agency->id,
        'assigned_agent_id' => $this->user->id,
        'contact_id' => $this->contact->id,
        'listing_id' => $this->listing->id,
        'title' => 'Jane Purchase',
        'type' => 'sale',
        'value' => 120000000.00,
        'pipeline_stage_id' => $this->stageInquiry->id,
    ]);

    $t = Livewire::test(App\Http\Livewire\Contracts\ContractsPage::class)
        ->set('showCreateForm', true)
        ->set('deal_id', $deal->id)
        ->set('contact_id', $this->contact->id)
        ->set('listing_id', $this->listing->id)
        ->set('selectedTemplate', 'sale_agreement');

    expect($t->get('body'))->toContain('Jane Doe');
    expect($t->get('body'))->toContain('120,000,000');
    expect($t->get('body'))->toContain('456 Automation Ave');

    $t->call('createContract');

    $this->assertDatabaseHas('contracts', [
        'deal_id' => $deal->id,
        'type' => 'sale_agreement',
        'status' => 'draft',
    ]);
});

it('simulates eSignature workflow flow state changes', function () {
    $contract = Contract::create([
        'agency_id' => $this->agency->id,
        'created_by' => $this->user->id,
        'title' => 'Test Agreement',
        'type' => 'sale_agreement',
        'status' => 'draft',
        'reference' => 'CON-12345678',
        'body' => 'I agree to the terms.',
    ]);

    // Send for signature
    $this->actingAs($this->user);
    Livewire::test(App\Http\Livewire\Contracts\ContractsPage::class)
        ->call('sendForSignature', $contract->id);

    $contract->refresh();
    expect($contract->status)->toBe('sent');
    expect($contract->signatories)->toHaveKey('envelope_id');

    // Sign the contract publicly
    Livewire::test(App\Http\Livewire\Contracts\PublicContractSignPage::class, ['reference' => 'CON-12345678'])
        ->set('fullName', 'Jane Doe')
        ->set('initials', 'JD')
        ->set('agreed', true)
        ->call('submitSignature')
        ->assertHasNoErrors();

    $contract->refresh();
    expect($contract->status)->toBe('fully_signed');
    expect($contract->signed_at)->toBeArray()->toHaveLength(1);
});

it('resolves commission splits dynamically based on config priority', function () {
    $this->actingAs($this->user);

    $deal = Deal::create([
        'agency_id' => $this->agency->id,
        'assigned_agent_id' => $this->user->id,
        'contact_id' => $this->contact->id,
        'listing_id' => $this->listing->id,
        'title' => 'Jane Purchase',
        'type' => 'sale',
        'value' => 100000.00, // Let's use 100,000 for easy Math
        'pipeline_stage_id' => $this->stageClosed->id,
    ]);

    // Setup Split config: Default 5% rate with 60% agent split
    CommissionSplitConfig::create([
        'agency_id' => $this->agency->id,
        'name' => 'Agency Default Split',
        'applies_to' => 'agency_default',
        'commission_rate' => 5.00,
        'agent_split' => 60.00,
        'agency_split' => 40.00,
        'is_active' => true,
    ]);

    // Setup Role split config: 5% rate with 70% split for "principal" role (user role)
    CommissionSplitConfig::create([
        'agency_id' => $this->agency->id,
        'name' => 'Principal Role Split',
        'applies_to' => 'role',
        'role' => 'principal',
        'commission_rate' => 5.00,
        'agent_split' => 70.00,
        'agency_split' => 30.00,
        'is_active' => true,
    ]);

    // Setup Agent-specific split config: 5% rate with 80% split for this specific user
    CommissionSplitConfig::create([
        'agency_id' => $this->agency->id,
        'name' => 'Agent Specific Split',
        'applies_to' => 'agent',
        'user_id' => $this->user->id,
        'commission_rate' => 5.00,
        'agent_split' => 80.00,
        'agency_split' => 20.00,
        'is_active' => true,
    ]);

    // 1. Reconcile with Agent Config active: Should get 80% agent split
    Livewire::test(App\Http\Livewire\Intelligence\CommissionLedgerPage::class)
        ->call('reconcile');

    $commission = Commission::where('deal_id', $deal->id)->first();
    expect($commission)->not->toBeNull();
    expect((float)$commission->agent_split_percentage)->toBe(80.00);
    expect((float)$commission->agent_commission)->toBe(4000.00); // 100,000 * 5% = 5,000 gross. 80% of 5,000 = 4,000

    // Cleanup and test Role Config fallback
    $commission->delete();
    CommissionSplitConfig::where('applies_to', 'agent')->delete();

    Livewire::test(App\Http\Livewire\Intelligence\CommissionLedgerPage::class)
        ->call('reconcile');

    $commission = Commission::where('deal_id', $deal->id)->first();
    expect((float)$commission->agent_split_percentage)->toBe(70.00); // 70% role split

    // Cleanup and test Default Config fallback
    $commission->delete();
    CommissionSplitConfig::where('applies_to', 'role')->delete();

    Livewire::test(App\Http\Livewire\Intelligence\CommissionLedgerPage::class)
        ->call('reconcile');

    $commission = Commission::where('deal_id', $deal->id)->first();
    expect((float)$commission->agent_split_percentage)->toBe(60.00); // 60% default split
});
