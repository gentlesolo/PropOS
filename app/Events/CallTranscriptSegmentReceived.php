<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class CallTranscriptSegmentReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly string $channel,
        public readonly array  $segment,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel($this->channel)];
    }

    public function broadcastAs(): string
    {
        return 'transcript.segment';
    }

    public function broadcastWith(): array
    {
        return [
            'speaker'   => $this->segment['speaker'],
            'text'      => $this->segment['text'],
            'is_final'  => $this->segment['is_final'],
            'start'     => $this->segment['start'],
        ];
    }
}
