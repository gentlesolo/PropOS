<?php

namespace App\Http\Livewire\Settings;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use Livewire\Component;

class NotificationsPage extends Component
{
    public bool  $leaseRemindersEnabled = true;
    public array $reminders = [];
    public array $notificationTemplates = [];

    // 4 flag columns exist on the leases table — this is the hard cap.
    private const MAX_REMINDERS = 4;

    private const DEFAULTS = [
        ['days' => 30, 'enabled' => true, 'tenant_subject' => '', 'tenant_body' => '', 'agent_subject' => '', 'agent_body' => ''],
        ['days' => 14, 'enabled' => true, 'tenant_subject' => '', 'tenant_body' => '', 'agent_subject' => '', 'agent_body' => ''],
        ['days' => 7,  'enabled' => true, 'tenant_subject' => '', 'tenant_body' => '', 'agent_subject' => '', 'agent_body' => ''],
        ['days' => 0,  'enabled' => true, 'tenant_subject' => '', 'tenant_body' => '', 'agent_subject' => '', 'agent_body' => ''],
    ];

    // Registry of all configurable in-app notification types.
    private const NOTIFICATION_TYPES = [
        'lease_created' => [
            'label'        => 'Lease Created',
            'group'        => 'Property Management',
            'description'  => 'Sent to the assigned agent when a new lease is recorded.',
            'placeholders' => ['{reference}', '{address}', '{tenant_name}'],
        ],
        'lease_expiry_reminder' => [
            'label'        => 'Lease Expiry Reminder',
            'group'        => 'Property Management',
            'description'  => 'In-app alert sent alongside the lease expiry email reminder.',
            'placeholders' => ['{reference}', '{address}', '{days}', '{end_date}'],
        ],
        'lease_terminated' => [
            'label'        => 'Lease Terminated',
            'group'        => 'Property Management',
            'description'  => 'Sent to the agent and managers when a lease is manually terminated.',
            'placeholders' => ['{reference}', '{address}', '{tenant_name}'],
        ],
        'lease_renewed' => [
            'label'        => 'Lease Renewed',
            'group'        => 'Property Management',
            'description'  => 'Sent when a lease renewal is confirmed.',
            'placeholders' => ['{reference}', '{address}'],
        ],
        'renewal_offer_sent' => [
            'label'        => 'Renewal Offer Sent',
            'group'        => 'Property Management',
            'description'  => 'Sent when a renewal offer is dispatched to the tenant.',
            'placeholders' => ['{reference}', '{address}'],
        ],
        'mandate_expiry' => [
            'label'        => 'Mandate Expiry',
            'group'        => 'Property Management',
            'description'  => 'Sent when a listing mandate is approaching its expiry date.',
            'placeholders' => ['{address}', '{expiry_date}'],
        ],
        'rent_overdue' => [
            'label'        => 'Rent Overdue',
            'group'        => 'Finance',
            'description'  => 'Sent to the agent when a rent payment becomes overdue.',
            'placeholders' => ['{tenant_name}', '{address}', '{amount}', '{days_overdue}'],
        ],
        'invoice_sent' => [
            'label'        => 'Invoice Sent',
            'group'        => 'Finance',
            'description'  => 'Sent to the agent when an invoice is dispatched to a client.',
            'placeholders' => ['{reference}', '{amount}'],
        ],
        'invoice_paid' => [
            'label'        => 'Invoice Paid',
            'group'        => 'Finance',
            'description'  => 'Sent when an invoice is marked as paid.',
            'placeholders' => ['{reference}', '{amount}'],
        ],
        'late_fee_applied' => [
            'label'        => 'Late Fee Applied',
            'group'        => 'Finance',
            'description'  => 'Sent when a late payment fee is charged to a tenant.',
            'placeholders' => ['{reference}', '{amount}', '{tenant_name}'],
        ],
        'expense_created' => [
            'label'        => 'Expense Submitted',
            'group'        => 'Finance',
            'description'  => 'Sent to managers when a new expense is submitted for review.',
            'placeholders' => ['{amount}', '{description}'],
        ],
        'expense_approved' => [
            'label'        => 'Expense Approved',
            'group'        => 'Finance',
            'description'  => 'Sent to the submitter when their expense is approved.',
            'placeholders' => ['{amount}', '{description}'],
        ],
        'expense_rejected' => [
            'label'        => 'Expense Rejected',
            'group'        => 'Finance',
            'description'  => 'Sent to the submitter when their expense is rejected.',
            'placeholders' => ['{amount}', '{description}'],
        ],
        'compliance_deadline' => [
            'label'        => 'Compliance Deadline',
            'group'        => 'Compliance',
            'description'  => 'Sent when a compliance item is approaching its deadline.',
            'placeholders' => ['{title}', '{due_date}'],
        ],
        'transaction' => [
            'label'        => 'Offer Accepted / Transaction',
            'group'        => 'Sales',
            'description'  => 'Sent when an offer is accepted and a transaction is created.',
            'placeholders' => ['{address}', '{amount}'],
        ],
        'performance_nudge' => [
            'label'        => 'Performance Nudge',
            'group'        => 'Performance',
            'description'  => 'Automated coaching alert based on agent activity metrics.',
            'placeholders' => ['{agent_name}', '{metric}'],
        ],
        'inbound_email' => [
            'label'        => 'New Email Thread',
            'group'        => 'Inbox',
            'description'  => 'Sent when a new inbound email thread is assigned to an agent.',
            'placeholders' => ['{sender}', '{subject}'],
        ],
        'rent_received' => [
            'label'        => 'Rent Payment Received',
            'group'        => 'Finance',
            'description'  => 'Sent when a rent payment is recorded against a lease.',
            'placeholders' => ['{tenant_name}', '{address}', '{amount}', '{reference}'],
        ],
        'viewing_booked' => [
            'label'        => 'Viewing Booked',
            'group'        => 'CRM & Viewings',
            'description'  => 'Sent to the agent when a prospect books a property viewing.',
            'placeholders' => ['{prospect_name}', '{address}', '{date}', '{time}'],
        ],
        'viewing_feedback' => [
            'label'        => 'Viewing Feedback Received',
            'group'        => 'CRM & Viewings',
            'description'  => 'Sent when a prospect submits feedback after a viewing.',
            'placeholders' => ['{prospect_name}', '{address}', '{rating}'],
        ],
        'new_lead' => [
            'label'        => 'New Lead Assigned',
            'group'        => 'CRM & Viewings',
            'description'  => 'Sent when a new enquiry or lead is assigned to an agent.',
            'placeholders' => ['{lead_name}', '{listing_ref}', '{address}'],
        ],
        'offer_received' => [
            'label'        => 'New Offer Received',
            'group'        => 'Sales',
            'description'  => 'Sent when a buyer submits an offer on a listing.',
            'placeholders' => ['{address}', '{amount}', '{expiry_date}'],
        ],
        'offer_accepted' => [
            'label'        => 'Offer Accepted',
            'group'        => 'Sales',
            'description'  => 'Sent when a buyer\'s offer is accepted by the seller.',
            'placeholders' => ['{address}', '{amount}'],
        ],
        'contract_signed' => [
            'label'        => 'Contract Signed',
            'group'        => 'Sales',
            'description'  => 'Sent when all parties have signed a contract.',
            'placeholders' => ['{address}', '{reference}'],
        ],
        'inspection_due' => [
            'label'        => 'Inspection Due',
            'group'        => 'Compliance',
            'description'  => 'Sent when a scheduled property inspection is upcoming.',
            'placeholders' => ['{address}', '{date}', '{time}'],
        ],
        'ai_credits_low' => [
            'label'        => 'AI Credits Low',
            'group'        => 'System',
            'description'  => 'Sent when the agency\'s AI credit balance falls below the warning threshold.',
            'placeholders' => ['{balance}'],
        ],
        'team_invitation_accepted' => [
            'label'        => 'Team Member Joined',
            'group'        => 'System',
            'description'  => 'Sent when a team invitation is accepted.',
            'placeholders' => ['{member_name}', '{role}'],
        ],
    ];

    private const TYPE_GROUPS = [
        'Property Management',
        'Finance',
        'CRM & Viewings',
        'Sales',
        'Compliance',
        'Performance',
        'Inbox',
        'System',
    ];

    public function mount(): void
    {
        $settings       = auth()->user()->agency?->settings ?? [];
        $leaseReminders = $settings['lease_reminders'] ?? [];

        $this->leaseRemindersEnabled = $leaseReminders['enabled'] ?? true;

        $saved = $leaseReminders['reminders'] ?? self::DEFAULTS;
        $this->reminders = array_map(fn ($r) => array_merge([
            'tenant_subject' => '',
            'tenant_body'    => '',
            'agent_subject'  => '',
            'agent_body'     => '',
        ], $r), $saved);

        // Load notification templates, backfilling any missing type keys.
        $savedTemplates = $settings['notification_templates'] ?? [];
        $templates      = [];
        foreach (array_keys(self::NOTIFICATION_TYPES) as $type) {
            $templates[$type] = array_merge(
                ['enabled' => true, 'title' => '', 'body' => ''],
                $savedTemplates[$type] ?? []
            );
        }
        $this->notificationTemplates = $templates;
    }

    public function addReminder(): void
    {
        if (count($this->reminders) >= self::MAX_REMINDERS) {
            $this->dispatch('notify', message: 'Maximum of ' . self::MAX_REMINDERS . ' reminders allowed.', type: 'warning');
            return;
        }

        $this->reminders[] = [
            'days'           => 3,
            'enabled'        => true,
            'tenant_subject' => '',
            'tenant_body'    => '',
            'agent_subject'  => '',
            'agent_body'     => '',
        ];
    }

    public function removeReminder(int $index): void
    {
        if (count($this->reminders) <= 1) {
            $this->dispatch('notify', message: 'At least one reminder must remain.', type: 'warning');
            return;
        }

        array_splice($this->reminders, $index, 1);
        $this->reminders = array_values($this->reminders);
    }

    public function generateEmailContent(int $index, string $audience): void
    {
        $agency = auth()->user()->agency;

        try {
            $agency->deductCredits(2, 'lease_reminder_email_generation');
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
            return;
        }

        $days     = (int) ($this->reminders[$index]['days'] ?? 0);
        $dayLabel = $days === 0 ? 'on the day the lease expires' : "{$days} days before the lease expires";
        $isAgent  = $audience === 'agent';

        $systemPrompt = 'You are a professional property management email copywriter. '
            . 'Return ONLY a JSON object with exactly two keys: "subject" (string, max 100 chars) and "body" (string). '
            . 'The body should be plain text, professional, and concise. '
            . 'Use these placeholders exactly where appropriate: '
            . ($isAgent
                ? '{agent_name}, {reference}, {address}, {end_date}, {days}'
                : '{first_name}, {full_name}, {reference}, {address}, {end_date}, {days}, {portal_url}')
            . '. Do not wrap in markdown or code fences.';

        $userPrompt = $isAgent
            ? "Write a lease expiry reminder email for a real estate agent. "
              . "The reminder is sent {$dayLabel}. "
              . "The agent needs to know the tenant name, property address, lease reference, and expiry date. "
              . "Keep it professional and action-oriented."
            : "Write a lease expiry reminder email for a tenant. "
              . "The reminder is sent {$dayLabel}. "
              . "The tenant should know their property address, lease reference, expiry date, and be encouraged to contact their property manager. "
              . "Include a portal URL if available. Keep it friendly but professional.";

        try {
            $ai  = app(AiCompletionServiceInterface::class);
            $raw = $ai->generate($systemPrompt, $userPrompt, ['temperature' => 0.7]);

            $raw    = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
            $raw    = preg_replace('/\s*```$/', '', trim($raw));
            $parsed = json_decode(trim($raw), true);

            if (! is_array($parsed) || empty($parsed['subject']) || empty($parsed['body'])) {
                $this->dispatch('notify', message: 'AI returned an unexpected response. Please try again.', type: 'error');
                return;
            }

            $this->reminders[$index]["{$audience}_subject"] = $parsed['subject'];
            $this->reminders[$index]["{$audience}_body"]    = $parsed['body'];

            $this->dispatch('notify', message: 'Email content generated successfully.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Failed to generate content. Please try again.', type: 'error');
        }
    }

    public function generateNotificationContent(string $type): void
    {
        if (! isset(self::NOTIFICATION_TYPES[$type])) {
            return;
        }

        $agency = auth()->user()->agency;

        try {
            $agency->deductCredits(1, 'notification_template_generation');
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
            return;
        }

        $meta         = self::NOTIFICATION_TYPES[$type];
        $placeholders = implode(', ', $meta['placeholders']);

        $systemPrompt = 'You are a property management software notification writer. '
            . 'Return ONLY a JSON object with exactly two keys: "title" (string, max 80 chars) and "body" (string, max 200 chars). '
            . 'These are short in-app bell notifications — concise, professional, and actionable. '
            . 'Use these placeholders where appropriate: ' . $placeholders . '. '
            . 'Do not wrap in markdown or code fences.';

        $userPrompt = "Write an in-app notification for the event: \"{$meta['label']}\". "
            . "Context: {$meta['description']} "
            . "Keep the title under 80 characters and the body to 1–2 sentences.";

        try {
            $ai  = app(AiCompletionServiceInterface::class);
            $raw = $ai->generate($systemPrompt, $userPrompt, ['temperature' => 0.7]);

            $raw    = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
            $raw    = preg_replace('/\s*```$/', '', trim($raw));
            $parsed = json_decode(trim($raw), true);

            if (! is_array($parsed) || empty($parsed['title']) || empty($parsed['body'])) {
                $this->dispatch('notify', message: 'AI returned an unexpected response. Please try again.', type: 'error');
                return;
            }

            $this->notificationTemplates[$type]['title'] = $parsed['title'];
            $this->notificationTemplates[$type]['body']  = $parsed['body'];

            $this->dispatch('notify', message: 'Notification content generated.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Failed to generate content. Please try again.', type: 'error');
        }
    }

    public function save(): void
    {
        $this->validate([
            'reminders'                       => 'required|array|min:1|max:' . self::MAX_REMINDERS,
            'reminders.*.days'                => 'required|integer|min:0|max:365',
            'reminders.*.enabled'             => 'required|boolean',
            'reminders.*.tenant_subject'      => 'nullable|string|max:200',
            'reminders.*.tenant_body'         => 'nullable|string|max:5000',
            'reminders.*.agent_subject'       => 'nullable|string|max:200',
            'reminders.*.agent_body'          => 'nullable|string|max:5000',
            'notificationTemplates'           => 'array',
            'notificationTemplates.*.enabled' => 'boolean',
            'notificationTemplates.*.title'   => 'nullable|string|max:200',
            'notificationTemplates.*.body'    => 'nullable|string|max:500',
        ]);

        $agency   = auth()->user()->agency;
        $settings = $agency->settings ?? [];

        $settings['lease_reminders'] = [
            'enabled'   => $this->leaseRemindersEnabled,
            'reminders' => $this->reminders,
        ];

        $settings['notification_templates'] = $this->notificationTemplates;

        $agency->update(['settings' => $settings]);
        $this->dispatch('notify', message: 'Notification settings saved.', type: 'success');
    }

    public function render()
    {
        $grouped = [];
        foreach (self::TYPE_GROUPS as $group) {
            $grouped[$group] = array_filter(
                self::NOTIFICATION_TYPES,
                fn ($t) => $t['group'] === $group
            );
        }

        return view('livewire.settings.notifications-page', [
            'maxReminders'    => self::MAX_REMINDERS,
            'groupedTypes'    => $grouped,
        ])->layout('layouts.app');
    }
}
