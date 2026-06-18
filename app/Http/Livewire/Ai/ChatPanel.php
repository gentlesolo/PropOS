<?php

namespace App\Http\Livewire\Ai;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\ChatMessage;
use App\Infrastructure\Persistence\Models\ChatSession;
use Livewire\Component;

class ChatPanel extends Component
{
    public $isOpen = false;
    public $sessionId = null;
    public $newMessage = '';
    public bool $isProcessing = false;

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
        $this->isProcessing = false;

        ChatMessage::create([
            'chat_session_id' => $this->sessionId,
            'role' => 'assistant',
            'content' => 'Hello! I am your VillaCRM Copilot. How can I help you manage your agency today?',
        ]);
    }

    public function sendMessage()
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

        $placeholder = ChatMessage::create([
            'chat_session_id' => $this->sessionId,
            'role' => 'assistant',
            'content' => null,
        ]);

        $messageId = $placeholder->id;
        $sessionId = $this->sessionId;

        // defer() sends the Livewire response first, then runs this after PHP-FPM
        // flushes the connection — no queue worker needed, works on shared hosting.
        defer(function () use ($messageId, $sessionId) {
            $placeholder = ChatMessage::find($messageId);
            if (! $placeholder) {
                return;
            }

            $history = ChatMessage::where('chat_session_id', $sessionId)
                ->where('id', '!=', $messageId)
                ->orderBy('id', 'asc')
                ->get()
                ->map(fn($msg) => ['role' => $msg->role, 'content' => $msg->content])
                ->toArray();

            array_unshift($history, [
                'role' => 'system',
                'content' => 'You are VillaCRM Copilot, an AI assistant for a real estate agency. You are helpful, concise, and knowledgeable about property management, CRM, and real estate pipelines.',
            ]);

            try {
                $ai = app(AiCompletionServiceInterface::class);
                $response = $ai->chat($history);
                $placeholder->update([
                    'content' => $response['content'] ?: 'I encountered an error. Please try again.',
                    'tool_calls' => $response['tool_calls'],
                ]);
            } catch (\Throwable) {
                $placeholder->update(['content' => 'AI response failed. Please try again.']);
            }
        });

        $this->isProcessing = true;
    }

    public function checkForResponse(): void
    {
        if (! $this->isProcessing || ! $this->sessionId) {
            return;
        }

        $hasPending = ChatMessage::where('chat_session_id', $this->sessionId)
            ->whereNull('content')
            ->exists();

        if (! $hasPending) {
            $this->isProcessing = false;
        }
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
