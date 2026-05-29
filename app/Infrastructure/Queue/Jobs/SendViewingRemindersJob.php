<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Persistence\Models\Viewing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendViewingRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        $this->send48HourReminders();
        $this->sendMorningReminders();
        $this->send1HourReminders();
    }

    private function send48HourReminders(): void
    {
        $viewings = Viewing::with(['contact', 'listing.property', 'assignedAgent'])
            ->where('status', 'scheduled')
            ->where('reminder_48h_sent', false)
            ->whereBetween('scheduled_at', [now()->addHours(47), now()->addHours(49)])
            ->get();

        foreach ($viewings as $viewing) {
            $this->sendReminderEmail($viewing, '48-hour');
            $viewing->update(['reminder_48h_sent' => true]);
        }
    }

    private function sendMorningReminders(): void
    {
        $viewings = Viewing::with(['contact', 'listing.property', 'assignedAgent'])
            ->where('status', 'scheduled')
            ->where('reminder_morning_sent', false)
            ->whereDate('scheduled_at', today())
            ->get();

        foreach ($viewings as $viewing) {
            $this->sendReminderEmail($viewing, 'morning');
            $viewing->update(['reminder_morning_sent' => true]);
        }
    }

    private function send1HourReminders(): void
    {
        $viewings = Viewing::with(['contact', 'listing.property', 'assignedAgent'])
            ->where('status', 'scheduled')
            ->where('reminder_1h_sent', false)
            ->whereBetween('scheduled_at', [now()->addMinutes(55), now()->addMinutes(65)])
            ->get();

        foreach ($viewings as $viewing) {
            $this->sendReminderEmail($viewing, '1-hour');
            $viewing->update(['reminder_1h_sent' => true]);
        }
    }

    private function sendReminderEmail(Viewing $viewing, string $type): void
    {
        $contact  = $viewing->contact;
        $property = $viewing->listing?->property;

        if (! $contact?->email) {
            return;
        }

        $address = $property
            ? "{$property->address_line_1}, {$property->city}"
            : 'the property';

        $time = $viewing->scheduled_at->format('l, F j \a\t g:ia');

        $subject = match ($type) {
            '48-hour' => "Viewing reminder: {$address}",
            'morning' => "Today's viewing: {$address}",
            '1-hour'  => "Your viewing starts in 1 hour — {$address}",
            default   => "Viewing reminder",
        };

        $agentName = $viewing->assignedAgent?->name ?? 'Your agent';
        $agentPhone = $viewing->assignedAgent?->phone ?? '';

        $body = match ($type) {
            '48-hour' => "Hi {$contact->first_name},\n\nThis is a reminder that your viewing of {$address} is scheduled for {$time}.\n\nPlease reply to confirm you're still coming.\n\nAgent: {$agentName}" . ($agentPhone ? " ({$agentPhone})" : '') . "\n\nWe look forward to seeing you!",
            'morning' => "Good morning {$contact->first_name},\n\nJust a reminder — your viewing of {$address} is today at " . $viewing->scheduled_at->format('g:ia') . ".\n\nAgent: {$agentName}" . ($agentPhone ? " ({$agentPhone})" : ''),
            '1-hour'  => "Hi {$contact->first_name},\n\nYour viewing of {$address} starts in 1 hour at " . $viewing->scheduled_at->format('g:ia') . ".\n\nSee you soon!\n\n{$agentName}",
            default   => "Viewing reminder for {$address} at {$time}.",
        };

        try {
            Mail::raw($body, function ($message) use ($contact, $subject) {
                $message->to($contact->email, $contact->full_name)->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error('Viewing reminder email failed', [
                'viewing_id' => $viewing->id,
                'type'       => $type,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
