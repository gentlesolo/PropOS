<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\Agency;
use Illuminate\Database\Seeder;

class NotificationTemplatesSeeder extends Seeder
{
    /**
     * Default in-app notification templates for every agency.
     *
     * Keys mirror NotificationsPage::NOTIFICATION_TYPES exactly.
     * Placeholders use {curly} syntax, interpolated by NotificationService.
     */
    private const DEFAULTS = [

        // ── Property Management ───────────────────────────────────────────────

        'lease_created' => [
            'enabled' => true,
            'title'   => 'New Lease Created — {reference}',
            'body'    => 'Lease {reference} for {tenant_name} at {address} has been recorded. Review the lease details and set a reminder for upcoming milestones.',
        ],
        'lease_expiry_reminder' => [
            'enabled' => true,
            'title'   => 'Lease {reference} Expires in {days} Day(s)',
            'body'    => 'Lease {reference} at {address} is due to expire on {end_date}. Contact the tenant now to discuss renewal or vacation plans.',
        ],
        'lease_terminated' => [
            'enabled' => true,
            'title'   => 'Lease Terminated — {reference}',
            'body'    => 'Lease {reference} for {tenant_name} at {address} has been terminated. Update the property\'s availability status and arrange a final inspection.',
        ],
        'lease_renewed' => [
            'enabled' => true,
            'title'   => 'Lease Renewed — {reference}',
            'body'    => 'Lease {reference} at {address} has been successfully renewed. Review the updated terms and ensure the new end date is captured.',
        ],
        'renewal_offer_sent' => [
            'enabled' => true,
            'title'   => 'Renewal Offer Sent — {reference}',
            'body'    => 'A lease renewal offer for {reference} at {address} has been dispatched to the tenant. Follow up if no response is received within 7 days.',
        ],
        'mandate_expiry' => [
            'enabled' => true,
            'title'   => 'Mandate Expiring — {address}',
            'body'    => 'The mandate for {address} expires on {expiry_date}. Contact the owner promptly to discuss renewal or re-listing terms.',
        ],

        // ── Finance ───────────────────────────────────────────────────────────

        'rent_overdue' => [
            'enabled' => true,
            'title'   => 'Rent Overdue — {address}',
            'body'    => '{tenant_name} at {address} has an outstanding rent of {amount}, now {days_overdue} day(s) overdue. Send a reminder and log any communication.',
        ],
        'rent_received' => [
            'enabled' => true,
            'title'   => 'Rent Received — {reference}',
            'body'    => 'Payment of {amount} has been received from {tenant_name} at {address}. Reference: {reference}.',
        ],
        'invoice_sent' => [
            'enabled' => true,
            'title'   => 'Invoice {reference} Sent',
            'body'    => 'Invoice {reference} for {amount} has been dispatched to the client. Follow up if payment is not received by the due date.',
        ],
        'invoice_paid' => [
            'enabled' => true,
            'title'   => 'Invoice {reference} Paid',
            'body'    => 'Invoice {reference} for {amount} has been marked as paid. The payment has been recorded against the client account.',
        ],
        'late_fee_applied' => [
            'enabled' => true,
            'title'   => 'Late Fee Applied — {reference}',
            'body'    => 'A late payment fee of {amount} has been charged to {tenant_name} under reference {reference}. Update the tenant accordingly.',
        ],
        'expense_created' => [
            'enabled' => true,
            'title'   => 'Expense Submitted for Approval',
            'body'    => 'An expense of {amount} for "{description}" has been submitted and is awaiting your review. Approve or reject it from the Finance module.',
        ],
        'expense_approved' => [
            'enabled' => true,
            'title'   => 'Expense Approved',
            'body'    => 'Your expense of {amount} for "{description}" has been approved. It will be processed in the next payment run.',
        ],
        'expense_rejected' => [
            'enabled' => true,
            'title'   => 'Expense Rejected',
            'body'    => 'Your expense of {amount} for "{description}" has been rejected. Contact your manager for further details before resubmitting.',
        ],

        // ── CRM & Viewings ────────────────────────────────────────────────────

        'new_lead' => [
            'enabled' => true,
            'title'   => 'New Lead — {lead_name}',
            'body'    => '{lead_name} has enquired about {address} (Ref: {listing_ref}). Follow up within 24 hours to maximise conversion.',
        ],
        'viewing_booked' => [
            'enabled' => true,
            'title'   => 'Viewing Booked — {address}',
            'body'    => '{prospect_name} has booked a viewing at {address} on {date} at {time}. Confirm the appointment and prepare the property.',
        ],
        'viewing_feedback' => [
            'enabled' => true,
            'title'   => 'Viewing Feedback — {address}',
            'body'    => '{prospect_name} submitted feedback for the viewing at {address}. Rating: {rating}. Review the feedback and follow up as appropriate.',
        ],

        // ── Sales ─────────────────────────────────────────────────────────────

        'offer_received' => [
            'enabled' => true,
            'title'   => 'Offer Received — {address}',
            'body'    => 'An offer of {amount} has been submitted for {address}. The offer expires on {expiry_date}. Review and respond promptly.',
        ],
        'offer_accepted' => [
            'enabled' => true,
            'title'   => 'Offer Accepted — {address}',
            'body'    => 'The offer of {amount} for {address} has been accepted. Proceed to the contract and compliance stage.',
        ],
        'transaction' => [
            'enabled' => true,
            'title'   => 'Transaction Created — {address}',
            'body'    => 'A transaction for {address} at {amount} has been opened. Assign an attorney and begin the compliance checklist.',
        ],
        'contract_signed' => [
            'enabled' => true,
            'title'   => 'Contract Signed — {reference}',
            'body'    => 'All parties have signed the contract for {address} (Ref: {reference}). Proceed with transfer and post-sale compliance steps.',
        ],

        // ── Compliance ────────────────────────────────────────────────────────

        'compliance_deadline' => [
            'enabled' => true,
            'title'   => 'Compliance Deadline — {title}',
            'body'    => 'The compliance item "{title}" is due on {due_date}. Upload or complete the required documentation to avoid delays.',
        ],
        'inspection_due' => [
            'enabled' => true,
            'title'   => 'Inspection Due — {address}',
            'body'    => 'A property inspection is scheduled at {address} on {date} at {time}. Confirm attendance and prepare the inspection checklist.',
        ],

        // ── Performance ───────────────────────────────────────────────────────

        'performance_nudge' => [
            'enabled' => true,
            'title'   => 'Performance Alert — {agent_name}',
            'body'    => 'Activity metric flagged for {agent_name}: {metric}. Review the pipeline and take action to stay on track.',
        ],

        // ── Inbox ─────────────────────────────────────────────────────────────

        'inbound_email' => [
            'enabled' => true,
            'title'   => 'New Email from {sender}',
            'body'    => 'You have a new inbound email from {sender} with subject "{subject}". Open your inbox to read and respond.',
        ],

        // ── System ────────────────────────────────────────────────────────────

        'ai_credits_low' => [
            'enabled' => true,
            'title'   => 'AI Credits Running Low',
            'body'    => 'Your agency\'s AI credit balance has dropped to {balance}. Top up or upgrade your plan to continue using AI-powered features.',
        ],
        'team_invitation_accepted' => [
            'enabled' => true,
            'title'   => '{member_name} Has Joined the Team',
            'body'    => '{member_name} has accepted the team invitation and joined as {role}. Review their access level in Team Settings.',
        ],
    ];

    public function run(): void
    {
        Agency::all()->each(function (Agency $agency): void {
            $settings  = $agency->settings ?? [];
            $existing  = $settings['notification_templates'] ?? [];

            // Merge defaults in, preserving any templates the agency has already customised.
            $merged = self::DEFAULTS;
            foreach ($existing as $type => $saved) {
                if (isset($merged[$type])) {
                    $merged[$type] = array_merge($merged[$type], $saved);
                }
            }

            $settings['notification_templates'] = $merged;
            $agency->update(['settings' => $settings]);
        });
    }
}
