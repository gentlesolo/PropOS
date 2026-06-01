<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Task;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskBoardTest extends TestCase
{
    use RefreshDatabase;

    protected Agency $agency;
    protected User $user;
    protected User $otherAgent;
    protected Contact $contact;
    protected Deal $deal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $this->agency = Agency::factory()->create(['id' => 1, 'slug' => 'demo']);
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
            'email' => 'principal@propos.app',
        ]);
        $this->otherAgent = User::factory()->create([
            'agency_id' => $this->agency->id,
            'email' => 'agent@propos.app',
        ]);

        setPermissionsTeamId($this->agency->id);
        $this->user->assignRole('principal');
        $this->otherAgent->assignRole('agent');

        $this->contact = Contact::create([
            'agency_id' => $this->agency->id,
            'first_name' => 'Adaeze',
            'last_name' => 'Johnson',
            'email' => 'adaeze@example.com',
            'status' => 'new',
            'type' => 'buyer',
        ]);

        $stage = \App\Infrastructure\Persistence\Models\PipelineStage::create([
            'agency_id' => $this->agency->id,
            'name' => 'Inquiry',
            'pipeline_type' => 'sale',
            'order' => 1,
        ]);

        $this->deal = Deal::create([
            'agency_id' => $this->agency->id,
            'pipeline_stage_id' => $stage->id,
            'contact_id' => $this->contact->id,
            'title' => 'Lekki Penthouse Sale',
            'pipeline_type' => 'sale',
            'status' => 'active',
        ]);
    }

    public function test_task_board_page_loads_and_renders_correctly()
    {
        $task = Task::create([
            'agency_id' => $this->agency->id,
            'title' => 'Follow up with Adaeze',
            'status' => 'pending',
            'priority' => 'high',
            'type' => 'call',
            'assigned_to' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test(\App\Http\Livewire\Tasks\TaskBoardPage::class)
            ->assertSee('Follow up with Adaeze')
            ->assertSee('High')
            ->assertSee('My Open Tasks');
    }

    public function test_can_create_a_task_with_validation()
    {
        $this->actingAs($this->user);

        Livewire::test(\App\Http\Livewire\Tasks\TaskBoardPage::class)
            ->call('openCreate')
            ->set('title', '')
            ->call('saveTask')
            ->assertHasErrors(['title' => 'required'])
            ->set('title', 'Prepare listing deck')
            ->set('type', 'document')
            ->set('priority', 'urgent')
            ->set('description', 'Make it look premium')
            ->set('assigned_to', $this->otherAgent->id)
            ->set('contact_id', $this->contact->id)
            ->set('deal_id', $this->deal->id)
            ->set('due_at', now()->addDays(2)->format('Y-m-d\TH:i'))
            ->call('saveTask')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('tasks', [
            'agency_id' => $this->agency->id,
            'title' => 'Prepare listing deck',
            'type' => 'document',
            'priority' => 'urgent',
            'assigned_to' => $this->otherAgent->id,
            'contact_id' => $this->contact->id,
            'deal_id' => $this->deal->id,
            'status' => 'pending',
        ]);
    }

    public function test_can_edit_and_update_an_existing_task()
    {
        $task = Task::create([
            'agency_id' => $this->agency->id,
            'title' => 'Inspect Lekki site',
            'status' => 'pending',
            'priority' => 'medium',
            'type' => 'viewing',
        ]);

        $this->actingAs($this->user);

        Livewire::test(\App\Http\Livewire\Tasks\TaskBoardPage::class)
            ->call('openEdit', $task->id)
            ->assertSet('title', 'Inspect Lekki site')
            ->assertSet('priority', 'medium')
            ->set('title', 'Inspect Lekki site (Urgent)')
            ->set('priority', 'urgent')
            ->set('status', 'in_progress')
            ->call('saveTask')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Inspect Lekki site (Urgent)',
            'priority' => 'urgent',
            'status' => 'in_progress',
        ]);
    }

    public function test_can_delete_a_task()
    {
        $task = Task::create([
            'agency_id' => $this->agency->id,
            'title' => 'Delete me task',
            'status' => 'pending',
            'priority' => 'low',
        ]);

        $this->actingAs($this->user);

        Livewire::test(\App\Http\Livewire\Tasks\TaskBoardPage::class)
            ->call('deleteTask', $task->id);

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_task_status_transitions_work_correctly()
    {
        $task = Task::create([
            'agency_id' => $this->agency->id,
            'title' => 'Workflow transitions',
            'status' => 'pending',
            'priority' => 'high',
        ]);

        $this->actingAs($this->user);

        // Start task -> In Progress
        Livewire::test(\App\Http\Livewire\Tasks\TaskBoardPage::class)
            ->call('startTask', $task->id);
        $this->assertEquals('in_progress', $task->fresh()->status);

        // Complete task -> Completed
        Livewire::test(\App\Http\Livewire\Tasks\TaskBoardPage::class)
            ->call('completeTask', $task->id);
        $task = $task->fresh();
        $this->assertEquals('completed', $task->status);
        $this->assertNotNull($task->completed_at);

        // Reopen task -> Pending
        Livewire::test(\App\Http\Livewire\Tasks\TaskBoardPage::class)
            ->call('reopenTask', $task->id);
        $task = $task->fresh();
        $this->assertEquals('pending', $task->status);
        $this->assertNull($task->completed_at);

        // Cancel task -> Cancelled
        Livewire::test(\App\Http\Livewire\Tasks\TaskBoardPage::class)
            ->call('cancelTask', $task->id);
        $this->assertEquals('cancelled', $task->fresh()->status);
    }

    public function test_opening_and_closing_detail_slideover()
    {
        $task = Task::create([
            'agency_id' => $this->agency->id,
            'title' => 'Show me details',
            'status' => 'pending',
            'priority' => 'low',
        ]);

        $this->actingAs($this->user);

        Livewire::test(\App\Http\Livewire\Tasks\TaskBoardPage::class)
            ->assertSet('showDetail', false)
            ->call('openDetail', $task->id)
            ->assertSet('showDetail', true)
            ->assertSet('detailId', $task->id)
            ->call('closeDetail')
            ->assertSet('showDetail', false)
            ->assertSet('detailId', null);
    }

    public function test_filtering_and_search_scopes_correctly()
    {
        // 1. Task assigned to user
        $myTask = Task::create([
            'agency_id' => $this->agency->id,
            'title' => 'Task for principal',
            'status' => 'pending',
            'priority' => 'high',
            'type' => 'call',
            'assigned_to' => $this->user->id,
        ]);

        // 2. Task assigned to other agent
        $otherTask = Task::create([
            'agency_id' => $this->agency->id,
            'title' => 'Task for assistant',
            'status' => 'pending',
            'priority' => 'low',
            'type' => 'email',
            'assigned_to' => $this->otherAgent->id,
        ]);

        $this->actingAs($this->user);

        // Test search
        Livewire::test(\App\Http\Livewire\Tasks\TaskBoardPage::class)
            ->set('search', 'principal')
            ->assertSee('Task for principal')
            ->assertDontSee('Task for assistant');

        // Test priority filter
        Livewire::test(\App\Http\Livewire\Tasks\TaskBoardPage::class)
            ->set('priorityFilter', 'low')
            ->assertSee('Task for assistant')
            ->assertDontSee('Task for principal');

        // Test type filter
        Livewire::test(\App\Http\Livewire\Tasks\TaskBoardPage::class)
            ->set('typeFilter', 'call')
            ->assertSee('Task for principal')
            ->assertDontSee('Task for assistant');

        // Test assignee filter
        Livewire::test(\App\Http\Livewire\Tasks\TaskBoardPage::class)
            ->set('assigneeFilter', $this->otherAgent->id)
            ->assertSee('Task for assistant')
            ->assertDontSee('Task for principal');

        // Test "my tasks only" toggle
        Livewire::test(\App\Http\Livewire\Tasks\TaskBoardPage::class)
            ->set('showMyTasksOnly', true)
            ->assertSee('Task for principal')
            ->assertDontSee('Task for assistant');
    }
}
