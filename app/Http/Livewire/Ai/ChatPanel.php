<?php

namespace App\Http\Livewire\Ai;

use Livewire\Component;
use App\Infrastructure\Persistence\Models\ChatSession;
use App\Infrastructure\Persistence\Models\ChatMessage;

class ChatPanel extends Component
{
    public $isOpen = false;
    public $sessionId = null;
    public $newMessage = '';
    
    protected $listeners = [
        'toggleChatPanel' => 'toggle',
        'open-chat-with-context' => 'openWithContext',
    ];

    public function toggle()
    {
        $this->isOpen = !$this->isOpen;
        if ($this->isOpen && !$this->sessionId) {
            $this->startNewSession();
        }
    }

    public function openWithContext(string $context)
    {
        if (!$this->isOpen) {
            $this->isOpen = true;
            $this->startNewSession();
        }
        // Pre-populate the message field with the context prompt
        $this->newMessage = $context;
    }

    public function startNewSession()
    {
        $session = ChatSession::create([
            'agency_id' => auth()->user()->agency_id,
            'user_id' => auth()->id(),
            'title' => 'New Conversation',
        ]);
        $this->sessionId = $session->id;
        
        // Add initial system greeting
        ChatMessage::create([
            'chat_session_id' => $this->sessionId,
            'role' => 'assistant',
            'content' => 'Hello! I am your PropOS Copilot. How can I help you manage your agency today?',
        ]);
    }

    public function sendMessage(\App\Domain\AI\Contracts\AiCompletionServiceInterface $aiService)
    {
        if (empty(trim($this->newMessage))) {
            return;
        }

        ChatMessage::create([
            'chat_session_id' => $this->sessionId,
            'role' => 'user',
            'content' => $this->newMessage,
        ]);

        $this->newMessage = '';

        $this->processAiResponse($aiService);
    }

    private function processAiResponse($aiService)
    {
        // Get message history for context
        $history = ChatMessage::where('chat_session_id', $this->sessionId)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($msg) {
                return ['role' => $msg->role, 'content' => $msg->content];
            })
            ->toArray();

        // Ensure system prompt is first
        array_unshift($history, [
            'role' => 'system',
            'content' => 'You are PropOS Copilot, an AI assistant for a real estate agency. You are helpful, concise, and knowledgeable about property management, CRM, and real estate pipelines.'
        ]);

        // Call the AI Service
        $response = $aiService->chat($history);

        // Store response
        ChatMessage::create([
            'chat_session_id' => $this->sessionId,
            'role' => 'assistant',
            'content' => $response['content'],
            'tool_calls' => $response['tool_calls'],
        ]);
    }

    public function getMessagesProperty()
    {
        if (!$this->sessionId) {
            return [];
        }
        return ChatMessage::where('chat_session_id', $this->sessionId)->orderBy('id', 'asc')->get();
    }

    public function render()
    {
        return view('livewire.ai.chat-panel');
    }
}
