<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Infrastructure\Notifications\NotificationService;
use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\ComplianceReminder;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendComplianceRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(NotificationService $notifications): void
    {
        // Mark any newly overdue items
        ComplianceReminder::where('status', 'pending')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        // Notify on items due within 3 days that haven't been notified in the last 24 hours
        $reminders = ComplianceReminder::with(['agency'])
            ->whereNotIn('status', ['completed'])
            ->where('due_date', '<=', now()->addDays(3)->toDateString())
            ->where(fn ($q) => $q->whereNull('notified_at')
                ->orWhere('notified_at', '<', now()->subHours(24)))
            ->get();

        foreach ($reminders as $reminder) {
            $this->notifyAgencyAdmins($reminder, $notifications);
            $reminder->update(['notified_at' => now()]);
        }

        Log::info('SendComplianceRemindersJob: processed', ['count' => $reminders->count()]);
    }

    private function notifyAgencyAdmins(ComplianceReminder $reminder, NotificationService $notifications): void
    {
        $admins = User::where('agency_id', $reminder->agency_id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['principal', 'admin', 'manager']))
            ->get();

        $daysLeft = now()->diffInDays($reminder->due_date, false);
        $urgencyLabel = $daysLeft < 0 ? 'OVERDUE' : ($daysLeft === 0 ? 'DUE TODAY' : "due in {$daysLeft} day(s)");

        foreach ($admins as $admin) {
            // In-app notification
            try {
                $notifications->notifyUser(
                    $admin->id,
                    'compliance_deadline',
                    "Compliance Reminder: {$reminder->title}",
                    ucfirst($reminder->reminder_type)." — {$urgencyLabel} ({$reminder->due_date->format('d M Y')})",
                    '/compliance/calendar',
                    $daysLeft < 0 ? 'danger' : 'warning',
                );
            } catch (\Exception $e) {
                Log::warning('Compliance in-app notification failed', ['reminder_id' => $reminder->id, 'user_id' => $admin->id, 'error' => $e->getMessage()]);
            }

            // Email notification
            if (! $admin->email) continue;

            $subject = "[Compliance] {$reminder->title} — {$urgencyLabel}";
            $body = "Hi {$admin->first_name},\n\n"
                . "This is an automated compliance reminder:\n\n"
                . "  Title: {$reminder->title}\n"
                . "  Type:  ".ucfirst(str_replace('_', ' ', $reminder->reminder_type))."\n"
                . "  Due:   {$reminder->due_date->format('d M Y')} ({$urgencyLabel})\n"
                . ($reminder->notes ? "  Notes: {$reminder->notes}\n" : '')
                . "\nView all compliance reminders: ".url('/compliance/calendar')."\n\n"
                . "This is an automated notification.";

            try {
                Mail::raw($body, fn ($msg) => $msg->to($admin->email, $admin->name)->subject($subject));
            } catch (\Exception $e) {
                Log::error('Compliance reminder email failed', ['reminder_id' => $reminder->id, 'user_id' => $admin->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
