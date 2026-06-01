<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Task;
use App\Infrastructure\Persistence\Models\Transaction;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $agency = Agency::where('slug', 'demo')->first();
        if (! $agency) {
            $this->command->warn('TaskSeeder: demo agency not found. Skipping.');
            return;
        }

        $agencyId  = $agency->id;
        $principal = User::withoutGlobalScopes()->where('email', 'principal@propos.app')->first();
        $agent     = User::withoutGlobalScopes()->where('email', 'agent@propos.app')->first();

        if (! $principal || ! $agent) {
            $this->command->warn('TaskSeeder: demo users not found. Skipping.');
            return;
        }

        $users        = collect([$principal, $agent]);
        $contacts     = Contact::where('agency_id', $agencyId)->get();
        $deals        = Deal::where('agency_id', $agencyId)->get();
        $listings     = Listing::where('agency_id', $agencyId)->get();
        $transactions = Transaction::where('agency_id', $agencyId)->get();

        // ── 1. Pending tasks spread across types & priorities ───────────────────
        $pendingTemplates = [
            ['title' => 'Follow up with buyer on Lekki property offer',        'type' => 'follow_up',  'priority' => 'urgent'],
            ['title' => 'Send comparable market analysis to client',            'type' => 'document',   'priority' => 'high'],
            ['title' => 'Schedule property viewing — Victoria Island duplex',   'type' => 'viewing',    'priority' => 'high'],
            ['title' => 'Call landlord to confirm lease renewal terms',         'type' => 'call',       'priority' => 'high'],
            ['title' => 'Draft offer letter for Ikoyi penthouse',               'type' => 'document',   'priority' => 'urgent'],
            ['title' => 'Email FICA documents checklist to Adaeze Johnson',     'type' => 'email',      'priority' => 'high'],
            ['title' => 'Prepare listing presentation for Ikeja GRA mandate',   'type' => 'meeting',    'priority' => 'medium'],
            ['title' => 'Update pipeline stage for Deal #4',                    'type' => 'other',      'priority' => 'medium'],
            ['title' => 'Arrange professional photography for new listing',     'type' => 'other',      'priority' => 'medium'],
            ['title' => 'Send welcome email to newly registered buyer lead',    'type' => 'email',      'priority' => 'low'],
            ['title' => 'Review open house RSVP list and confirm attendees',    'type' => 'other',      'priority' => 'medium'],
            ['title' => 'Upload signed mandate to document vault',              'type' => 'document',   'priority' => 'high'],
        ];

        foreach ($pendingTemplates as $idx => $tpl) {
            $assignee  = $users->get($idx % 2);
            $contact   = $contacts->get($idx % max($contacts->count(), 1));
            $deal      = $idx % 3 === 0 ? $deals->get($idx % max($deals->count(), 1)) : null;
            $listing   = $idx % 4 === 0 ? $listings->get($idx % max($listings->count(), 1)) : null;

            Task::firstOrCreate(
                ['agency_id' => $agencyId, 'title' => $tpl['title']],
                [
                    'created_by'  => $principal->id,
                    'assigned_to' => $assignee->id,
                    'contact_id'  => $contact?->id,
                    'deal_id'     => $deal?->id,
                    'listing_id'  => $listing?->id,
                    'type'        => $tpl['type'],
                    'priority'    => $tpl['priority'],
                    'status'      => 'pending',
                    'description' => $this->descriptionFor($tpl['type']),
                    'due_at'      => now()->addDays(rand(1, 10)),
                ]
            );
        }

        // ── 2. In-progress tasks ────────────────────────────────────────────────
        $inProgressTemplates = [
            ['title' => 'Negotiate counter-offer for Abuja Central property',  'type' => 'meeting',   'priority' => 'urgent'],
            ['title' => 'Coordinate conveyancing attorney for transfer',        'type' => 'document',  'priority' => 'high'],
            ['title' => 'Collect tenant deposit and issue receipt',             'type' => 'other',     'priority' => 'high'],
            ['title' => 'Run lead qualification call — investor inquiry',       'type' => 'call',      'priority' => 'medium'],
        ];

        foreach ($inProgressTemplates as $idx => $tpl) {
            $assignee = $users->get($idx % 2);
            $contact  = $contacts->get(($idx + 3) % max($contacts->count(), 1));
            $deal     = $deals->get($idx % max($deals->count(), 1));

            Task::firstOrCreate(
                ['agency_id' => $agencyId, 'title' => $tpl['title']],
                [
                    'created_by'  => $principal->id,
                    'assigned_to' => $assignee->id,
                    'contact_id'  => $contact?->id,
                    'deal_id'     => $deal?->id,
                    'type'        => $tpl['type'],
                    'priority'    => $tpl['priority'],
                    'status'      => 'in_progress',
                    'description' => $this->descriptionFor($tpl['type']),
                    'due_at'      => now()->addDays(rand(1, 5)),
                ]
            );
        }

        // ── 3. Completed tasks (with timestamps) ────────────────────────────────
        $completedTemplates = [
            ['title' => 'Send congratulations email on successful sale closure',    'type' => 'email',      'priority' => 'low'],
            ['title' => 'Submit commission statement to accounts department',       'type' => 'document',   'priority' => 'high'],
            ['title' => 'Verify FICA compliance for Chukwudi Okafor',              'type' => 'document',   'priority' => 'high'],
            ['title' => 'Initial buyer consultation — Lekki Phase 1',              'type' => 'meeting',    'priority' => 'medium'],
            ['title' => 'Post listing on Property24 and Private Property',         'type' => 'other',      'priority' => 'medium'],
            ['title' => 'Request deposit proof of payment from tenant',            'type' => 'follow_up',  'priority' => 'medium'],
            ['title' => 'Sign sole mandate agreement with seller',                 'type' => 'document',   'priority' => 'urgent'],
            ['title' => 'Send monthly activity report to principal',               'type' => 'email',      'priority' => 'low'],
        ];

        foreach ($completedTemplates as $idx => $tpl) {
            $assignee    = $users->get($idx % 2);
            $contact     = $contacts->get(($idx + 5) % max($contacts->count(), 1));
            $transaction = $idx % 3 === 0 ? $transactions->get($idx % max($transactions->count(), 1)) : null;

            $completedAt = now()->subDays(rand(1, 21));

            Task::firstOrCreate(
                ['agency_id' => $agencyId, 'title' => $tpl['title']],
                [
                    'created_by'     => $principal->id,
                    'assigned_to'    => $assignee->id,
                    'contact_id'     => $contact?->id,
                    'transaction_id' => $transaction?->id,
                    'type'           => $tpl['type'],
                    'priority'       => $tpl['priority'],
                    'status'         => 'completed',
                    'description'    => $this->descriptionFor($tpl['type']),
                    'due_at'         => $completedAt->copy()->subDays(1),
                    'completed_at'   => $completedAt,
                ]
            );
        }

        // ── 4. Overdue tasks (due in the past, still pending) ───────────────────
        $overdueTemplates = [
            ['title' => 'Chase outstanding rental payment — Contact 3',        'type' => 'follow_up',  'priority' => 'urgent'],
            ['title' => 'Renew expired listing mandate — Ikeja GRA',           'type' => 'document',   'priority' => 'urgent'],
            ['title' => 'Schedule compliance inspection for commercial unit',   'type' => 'viewing',    'priority' => 'high'],
        ];

        foreach ($overdueTemplates as $idx => $tpl) {
            $assignee = $users->get($idx % 2);
            $contact  = $contacts->get(($idx + 8) % max($contacts->count(), 1));

            Task::firstOrCreate(
                ['agency_id' => $agencyId, 'title' => $tpl['title']],
                [
                    'created_by'  => $principal->id,
                    'assigned_to' => $assignee->id,
                    'contact_id'  => $contact?->id,
                    'type'        => $tpl['type'],
                    'priority'    => $tpl['priority'],
                    'status'      => 'pending',
                    'description' => $this->descriptionFor($tpl['type']),
                    'due_at'      => now()->subDays(rand(2, 14)), // past due = overdue
                ]
            );
        }

        $this->command->info('TaskSeeder: ' . Task::where('agency_id', $agencyId)->count() . ' tasks seeded.');
    }

    private function descriptionFor(string $type): string
    {
        return match ($type) {
            'call'      => 'Call the client to discuss the latest update on their property requirement and confirm next steps.',
            'email'     => 'Draft and send a professional email with all relevant attachments and action items clearly listed.',
            'meeting'   => 'Prepare agenda, confirm venue or video link, and share pre-read materials at least 24 hours in advance.',
            'document'  => 'Complete, review for accuracy, obtain required signatures, and file in the document vault.',
            'follow_up' => 'Follow up on the outstanding action item. If no response after 48 hours, escalate to principal.',
            'viewing'   => 'Coordinate access with the seller or landlord, confirm the time with the buyer, and send a confirmation SMS.',
            default     => 'Complete this task and update its status in the task board when done.',
        };
    }
}
