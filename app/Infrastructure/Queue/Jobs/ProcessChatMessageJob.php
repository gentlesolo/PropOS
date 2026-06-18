<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessChatMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        public readonly int $assistantMessageId,
        public readonly int $chatSessionId,
    ) {}

    public function handle(AiCompletionServiceInterface $ai): void
    {
        $placeholder = ChatMessage::find($this->assistantMessageId);
        if (! $placeholder) {
            return;
        }

        $history = ChatMessage::where('chat_session_id', $this->chatSessionId)
            ->where('id', '!=', $this->assistantMessageId)
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn($msg) => ['role' => $msg->role, 'content' => $msg->content])
            ->toArray();

        array_unshift($history, [
            'role' => 'system',
            'content' => 'You are VillaCRM Copilot, an AI assistant for a real estate agency. You are helpful, concise, and knowledgeable about property management, CRM, and real estate pipelines.',
        ]);

        $response = $ai->chat($history);

        $placeholder->update([
            'content' => $response['content'] ?: 'I encountered an error. Please try again.',
            'tool_calls' => $response['tool_calls'],
        ]);
    }

    public function failed(\Throwable $e): void
    {
        ChatMessage::where('id', $this->assistantMessageId)->update([
            'content' => 'AI response failed. Please try again.',
        ]);
    }
}
