<?php

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\ChatSession;
use App\Infrastructure\Persistence\Models\ChatMessage;
use App\Http\Livewire\Ai\ChatPanel;
use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('chat panel allows initiating a session and sending a message', function () {
    $this->seed(Database\Seeders\RoleAndPermissionSeeder::class);

    $agency = Agency::factory()->create();
    $user = User::factory()->create(['agency_id' => $agency->id]);

    setPermissionsTeamId($agency->id);
    $user->assignRole('agent');

    $this->actingAs($user);

    // Mock AiCompletionServiceInterface
    $mockService = Mockery::mock(AiCompletionServiceInterface::class);
    $mockService->shouldReceive('chat')
        ->once()
        ->andReturn([
            'content' => 'This is a simulated response.',
            'tool_calls' => null,
        ]);

    app()->instance(AiCompletionServiceInterface::class, $mockService);

    Livewire::test(ChatPanel::class)
        ->call('toggle')
        ->assertSet('isOpen', true)
        ->set('newMessage', 'Hello, Copilot!')
        ->call('sendMessage')
        ->assertSet('newMessage', '');

    // Assert session was created
    $session = ChatSession::where('user_id', $user->id)->first();
    expect($session)->not->toBeNull();

    // Assert messages were stored
    $messages = ChatMessage::where('chat_session_id', $session->id)->orderBy('id')->get();
    expect($messages->count())->toBe(3); // Initial greeting, user message, AI response
    expect($messages[1]->content)->toBe('Hello, Copilot!');
    expect($messages[2]->content)->toBe('This is a simulated response.');
});
