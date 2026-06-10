<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\Notification;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('status', 'active')->with('agency')->get();

        // Wipe existing seeded notifications so re-runs don't stack duplicates.
        Notification::withoutGlobalScopes()
            ->whereIn('user_id', $users->pluck('id'))
            ->delete();

        foreach ($users as $user) {
            $this->seedForUser($user);
        }
    }

    private function seedForUser(User $user): void
    {
        $agencyId = $user->agency_id;

        $notifications = [

            // ── Property Management ───────────────────────────────────────────

            [
                'type'       => 'lease_created',
                'title'      => 'New Lease Created',
                'body'       => 'Lease REF-2024-0055 for Amaka Obi at 6 Glover Road, Ikoyi has been created. Lease period: ' . now()->subMonths(2)->format('d M Y') . ' – ' . now()->addMonths(10)->format('d M Y') . '.',
                'action_url' => '/property-management/leases',
                'severity'   => 'info',
                'read_at'    => now()->subDays(3),
                'created_at' => now()->subDays(3),
            ],
            [
                'type'       => 'lease_expiry_reminder',
                'title'      => 'Lease Expires in 7 Days',
                'body'       => 'Lease REF-2024-0042 for 14 Orchid Close, Lekki expires on ' . now()->addDays(7)->format('d M Y') . '. Contact the tenant to discuss renewal.',
                'action_url' => '/property-management/leases',
                'severity'   => 'warning',
                'read_at'    => null,
                'created_at' => now()->subHours(2),
            ],
            [
                'type'       => 'lease_expiry_reminder',
                'title'      => 'Lease Expires Today',
                'body'       => 'Lease REF-2024-0031 for 5B Marina Court expires today. Please follow up with the tenant immediately.',
                'action_url' => '/property-management/leases',
                'severity'   => 'error',
                'read_at'    => null,
                'created_at' => now()->subHours(5),
            ],
            [
                'type'       => 'lease_expiry_reminder',
                'title'      => 'Lease Expires in 30 Days',
                'body'       => 'Lease REF-2024-0019 for 22 Palm Avenue, Ikoyi expires on ' . now()->addDays(30)->format('d M Y') . '. Begin renewal discussions early.',
                'action_url' => '/property-management/leases',
                'severity'   => 'info',
                'read_at'    => now()->subDay(),
                'created_at' => now()->subDays(1),
            ],
            [
                'type'       => 'lease_terminated',
                'title'      => 'Lease Terminated',
                'body'       => 'Lease REF-2024-0038 for Kelechi Eze at 11 Awolowo Road has been terminated effective ' . now()->subDays(5)->format('d M Y') . '. Update property availability accordingly.',
                'action_url' => '/property-management/leases',
                'severity'   => 'warning',
                'read_at'    => now()->subDays(5),
                'created_at' => now()->subDays(5),
            ],
            [
                'type'       => 'lease_renewed',
                'title'      => 'Lease Renewed Successfully',
                'body'       => 'Lease REF-2024-0021 for 3 Admiralty Way, Lekki Phase 1 has been renewed for another 12 months. New expiry: ' . now()->addYear()->format('d M Y') . '.',
                'action_url' => '/property-management/leases',
                'severity'   => 'info',
                'read_at'    => now()->subDays(2),
                'created_at' => now()->subDays(2),
            ],
            [
                'type'       => 'renewal_offer_sent',
                'title'      => 'Renewal Offer Sent to Tenant',
                'body'       => 'A lease renewal offer has been sent to Funmi Adeyemi for 9 Bishop Aboyade Cole Street. Awaiting tenant response.',
                'action_url' => '/property-management/leases',
                'severity'   => 'info',
                'read_at'    => null,
                'created_at' => now()->subHours(10),
            ],
            [
                'type'       => 'mandate_expiry',
                'title'      => 'Mandate Expiring in 14 Days',
                'body'       => 'The sales mandate for 18 Ozumba Mbadiwe Avenue expires on ' . now()->addDays(14)->format('d M Y') . '. Contact the owner to discuss renewal.',
                'action_url' => '/listings',
                'severity'   => 'warning',
                'read_at'    => null,
                'created_at' => now()->subHours(6),
            ],

            // ── Finance ───────────────────────────────────────────────────────

            [
                'type'       => 'rent_overdue',
                'title'      => 'Rent Overdue — 3 Days',
                'body'       => 'Tenant Emeka Okafor at 8 Banana Island Road has not paid rent due on ' . now()->subDays(3)->format('d M Y') . '. Outstanding: ₦850,000.',
                'action_url' => '/property-management/rent-collection',
                'severity'   => 'warning',
                'read_at'    => null,
                'created_at' => now()->subHours(8),
            ],
            [
                'type'       => 'rent_received',
                'title'      => 'Rent Payment Received',
                'body'       => 'Payment of ₦1,200,000 received from Chidinma Eze for 3 Admiralty Way, Lekki Phase 1. Reference: TRX-88821.',
                'action_url' => '/property-management/rent-collection',
                'severity'   => 'info',
                'read_at'    => now()->subHours(1),
                'created_at' => now()->subHours(1),
            ],
            [
                'type'       => 'invoice_sent',
                'title'      => 'Invoice Sent to Client',
                'body'       => 'Invoice INV-2024-0089 for ₦250,000 (legal fees — 12 Kofo Abayomi) has been dispatched to the client. Due in 14 days.',
                'action_url' => '/finance/invoices',
                'severity'   => 'info',
                'read_at'    => now()->subDays(2),
                'created_at' => now()->subDays(2),
            ],
            [
                'type'       => 'invoice_paid',
                'title'      => 'Invoice Paid',
                'body'       => 'Invoice INV-2024-0072 for ₦180,000 (agency commission — 5 Gerrard Road) has been marked as paid.',
                'action_url' => '/finance/invoices',
                'severity'   => 'info',
                'read_at'    => now()->subDays(1),
                'created_at' => now()->subDays(1),
            ],
            [
                'type'       => 'late_fee_applied',
                'title'      => 'Late Payment Fee Applied',
                'body'       => 'A late fee of ₦42,500 has been charged to Obinna Nwosu for overdue rent at 2 Walter Carrington Crescent.',
                'action_url' => '/finance/invoices',
                'severity'   => 'info',
                'read_at'    => now()->subDays(4),
                'created_at' => now()->subDays(4),
            ],
            [
                'type'       => 'expense_created',
                'title'      => 'Expense Submitted for Approval',
                'body'       => 'Tobi Adegoke submitted an expense of ₦65,000 for property maintenance at 7 Kofo Abayomi Street. Awaiting your approval.',
                'action_url' => '/finance/expenses',
                'severity'   => 'info',
                'read_at'    => null,
                'created_at' => now()->subHours(3),
            ],
            [
                'type'       => 'expense_approved',
                'title'      => 'Expense Approved',
                'body'       => 'Your expense of ₦65,000 for property maintenance at 7 Kofo Abayomi Street has been approved.',
                'action_url' => '/finance/expenses',
                'severity'   => 'info',
                'read_at'    => now()->subHours(2),
                'created_at' => now()->subHours(2),
            ],
            [
                'type'       => 'expense_rejected',
                'title'      => 'Expense Rejected',
                'body'       => 'Your expense of ₦120,000 for equipment purchase has been rejected. Please contact your manager for details.',
                'action_url' => '/finance/expenses',
                'severity'   => 'warning',
                'read_at'    => now()->subDays(3),
                'created_at' => now()->subDays(3),
            ],

            // ── CRM & Viewings ────────────────────────────────────────────────

            [
                'type'       => 'new_lead',
                'title'      => 'New Lead Assigned',
                'body'       => 'Tunde Balogun has enquired about REF-LIS-0087 (4-bedroom duplex, Maitama). Follow up within 24 hours.',
                'action_url' => '/crm/contacts',
                'severity'   => 'info',
                'read_at'    => null,
                'created_at' => now()->subMinutes(20),
            ],
            [
                'type'       => 'viewing_booked',
                'title'      => 'New Viewing Booked',
                'body'       => 'Adaeze Nwosu has booked a viewing for 12 Bourdillon Road, Ikoyi on ' . now()->addDays(2)->format('d M Y') . ' at 10:00 AM.',
                'action_url' => '/viewings/day',
                'severity'   => 'info',
                'read_at'    => null,
                'created_at' => now()->subMinutes(45),
            ],
            [
                'type'       => 'viewing_feedback',
                'title'      => 'Viewing Feedback Received',
                'body'       => 'Feedback submitted for the viewing of 7 Kofo Abayomi Street. Rating: 4/5 — "Great property, needs minor renovation."',
                'action_url' => '/viewings/day',
                'severity'   => 'info',
                'read_at'    => now()->subHours(3),
                'created_at' => now()->subHours(3),
            ],

            // ── Sales ─────────────────────────────────────────────────────────

            [
                'type'       => 'offer_received',
                'title'      => 'New Offer Received',
                'body'       => 'An offer of ₦45,000,000 has been submitted for 18 Ozumba Mbadiwe Avenue. Review and respond before ' . now()->addDays(3)->format('d M Y') . '.',
                'action_url' => '/offers',
                'severity'   => 'warning',
                'read_at'    => null,
                'created_at' => now()->subHours(1),
            ],
            [
                'type'       => 'offer_accepted',
                'title'      => 'Offer Accepted',
                'body'       => 'Your counter-offer of ₦48,500,000 for 3 Gerrard Road, Ikoyi has been accepted by the buyer. Proceed to contract stage.',
                'action_url' => '/offers',
                'severity'   => 'info',
                'read_at'    => now()->subHours(6),
                'created_at' => now()->subHours(6),
            ],
            [
                'type'       => 'transaction',
                'title'      => 'Transaction Created',
                'body'       => 'A new transaction has been opened for 3 Gerrard Road, Ikoyi (₦48,500,000). Reference: TXN-2024-0201. Assign an attorney to proceed.',
                'action_url' => '/compliance/transactions',
                'severity'   => 'info',
                'read_at'    => now()->subHours(5),
                'created_at' => now()->subHours(5),
            ],
            [
                'type'       => 'contract_signed',
                'title'      => 'Contract Signed',
                'body'       => 'Both parties have signed the Offer to Purchase for 9 Akin Adesola Street. Transaction reference: TXN-2024-0193.',
                'action_url' => '/compliance/transactions',
                'severity'   => 'info',
                'read_at'    => now()->subDays(2),
                'created_at' => now()->subDays(2),
            ],

            // ── Compliance ────────────────────────────────────────────────────

            [
                'type'       => 'compliance_deadline',
                'title'      => 'Compliance Deadline in 3 Days',
                'body'       => 'FICA documentation for transaction TXN-2024-0186 must be submitted by ' . now()->addDays(3)->format('d M Y') . '. Upload documents now to avoid delays.',
                'action_url' => '/compliance/transactions',
                'severity'   => 'warning',
                'read_at'    => null,
                'created_at' => now()->subHours(7),
            ],
            [
                'type'       => 'inspection_due',
                'title'      => 'Inspection Due This Week',
                'body'       => '3 routine inspections are scheduled for this week. Next: 14 Orchid Close on ' . now()->addDays(3)->format('d M Y') . ' at 2:00 PM.',
                'action_url' => '/compliance/inspections',
                'severity'   => 'info',
                'read_at'    => null,
                'created_at' => now()->subHours(4),
            ],

            // ── Performance ───────────────────────────────────────────────────

            [
                'type'       => 'performance_nudge',
                'title'      => 'You Have 4 Neglected Leads',
                'body'       => 'You have 4 contacts with no activity in the past 7 days. A quick follow-up can make a big difference — check your pipeline now.',
                'action_url' => '/crm/pipeline',
                'severity'   => 'info',
                'read_at'    => now()->subDays(1),
                'created_at' => now()->subDays(1),
            ],

            // ── Inbox ─────────────────────────────────────────────────────────

            [
                'type'       => 'inbound_email',
                'title'      => 'New Email: Rental Enquiry',
                'body'       => 'New email from Chukwudi Nnaji (chukwudi@gmail.com): "Is the 3-bed on Victoria Island still available?" Assigned to your inbox.',
                'action_url' => '/marketing/inbox',
                'severity'   => 'info',
                'read_at'    => null,
                'created_at' => now()->subMinutes(15),
            ],

            // ── System ────────────────────────────────────────────────────────

            [
                'type'       => 'ai_credits_low',
                'title'      => 'AI Credits Running Low',
                'body'       => 'Your agency has fewer than 20 AI credits remaining. Upgrade your plan or purchase a top-up to continue using AI features.',
                'action_url' => '/settings/billing',
                'severity'   => 'warning',
                'read_at'    => null,
                'created_at' => now()->subHours(12),
            ],
            [
                'type'       => 'team_invitation_accepted',
                'title'      => 'Team Member Joined',
                'body'       => 'Ngozi Adekunle has accepted your team invitation and joined as an Agent.',
                'action_url' => '/settings/team',
                'severity'   => 'info',
                'read_at'    => now()->subDays(1),
                'created_at' => now()->subDays(1),
            ],

        ];

        foreach ($notifications as $data) {
            Notification::create(array_merge($data, [
                'agency_id'  => $agencyId,
                'user_id'    => $user->id,
                'updated_at' => $data['created_at'],
            ]));
        }
    }
}
