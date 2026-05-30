<?php

namespace App\Application\Offers\Actions;

use App\Infrastructure\Persistence\Models\Offer;
use App\Infrastructure\Persistence\Models\Transaction;
use App\Infrastructure\Persistence\Models\ComplianceDocument;
use App\Infrastructure\Persistence\Models\Task;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAcceptedOfferAction
{
    private NotificationService $notifications;

    public function __construct(NotificationService $notifications)
    {
        $this->notifications = $notifications;
    }

    public function execute(Offer $offer): Transaction
    {
        return DB::transaction(function () use ($offer) {
            $deal = $offer->deal;
            $listing = $offer->listing;
            
            // 1. Create Transaction
            $transaction = Transaction::create([
                'agency_id' => $offer->agency_id,
                'deal_id' => $offer->deal_id,
                'listing_id' => $offer->listing_id,
                'contact_id' => $offer->contact_id,
                'assigned_agent_id' => $deal->assigned_agent_id ?? $offer->submitted_by,
                'status' => 'fica_pending',
                'sale_price' => $offer->amount,
                'commission_rate' => $listing->commission_rate ?? 5.00,
                'agent_split' => $offer->agency->commission_splits['agent'] ?? 50.00,
                'offer_date' => now(),
                'deadline' => $offer->expiry_date,
                'timeline' => [
                    [
                        'event' => 'Offer Accepted',
                        'description' => "Offer of " . number_format($offer->amount, 2) . " accepted.",
                        'occurred_at' => now()->toDateTimeString(),
                    ]
                ],
                'notes' => "Automated transaction created from accepted offer #{$offer->id}."
            ]);

            // 2. Auto-generate compliance/FICA checklist
            $documents = [
                ['title' => 'Buyer ID Document', 'type' => 'buyer_id', 'is_fica' => true],
                ['title' => 'Buyer Proof of Address', 'type' => 'buyer_proof_of_address', 'is_fica' => true],
                ['title' => 'Seller ID Document', 'type' => 'seller_id', 'is_fica' => true],
                ['title' => 'Seller Proof of Address', 'type' => 'seller_proof_of_address', 'is_fica' => true],
                ['title' => 'Signed Offer to Purchase (OTP)', 'type' => 'signed_otp', 'is_fica' => false],
            ];

            foreach ($documents as $doc) {
                ComplianceDocument::create([
                    'transaction_id' => $transaction->id,
                    'agency_id' => $transaction->agency_id,
                    'document_type' => $doc['type'],
                    'title' => $doc['title'],
                    'status' => 'required',
                    'is_fica_required' => $doc['is_fica'],
                ]);
            }

            // 3. Auto-create milestone tasks
            $milestones = [
                ['title' => 'Collect Buyer & Seller FICA Documents', 'due_days' => 3, 'priority' => 'urgent'],
                ['title' => 'Assign Transferring / Conveyancing Attorney', 'due_days' => 5, 'priority' => 'high'],
                ['title' => 'Follow up on Bond / Mortgage Approval', 'due_days' => 14, 'priority' => 'medium'],
                ['title' => 'Lodge Transfer Documents with Deed Office', 'due_days' => 30, 'priority' => 'medium'],
            ];

            foreach ($milestones as $milestone) {
                Task::create([
                    'agency_id' => $transaction->agency_id,
                    'assigned_to' => $transaction->assigned_agent_id,
                    'created_by' => $offer->submitted_by,
                    'contact_id' => $transaction->contact_id,
                    'deal_id' => $transaction->deal_id,
                    'listing_id' => $transaction->listing_id,
                    'transaction_id' => $transaction->id,
                    'title' => $milestone['title'],
                    'status' => 'pending',
                    'priority' => $milestone['priority'],
                    'due_at' => now()->addDays($milestone['due_days']),
                ]);
            }

            // 4. Notify principal and assigned agent
            $agent = User::find($transaction->assigned_agent_id);
            if ($agent) {
                $this->notifications->notifyUser(
                    $agent,
                    'transaction',
                    'New Transaction Created',
                    "Offer accepted. Transaction #{$transaction->reference} has been generated automatically.",
                    route('compliance.transaction.detail', $transaction->id),
                    'success'
                );
            }

            // Notify principal
            $principals = User::where('agency_id', $transaction->agency_id)
                ->whereHas('roles', fn ($q) => $q->where('name', 'principal'))
                ->get();

            foreach ($principals as $principal) {
                $this->notifications->notifyUser(
                    $principal,
                    'transaction',
                    'Offer Accepted - Action Required',
                    "An offer for listing #{$transaction->listing_id} has been accepted. Transaction reference: {$transaction->reference}.",
                    route('compliance.transaction.detail', $transaction->id),
                    'info'
                );
            }

            // Update Deal Stage to "Offer Accepted"
            $acceptedStage = \App\Infrastructure\Persistence\Models\PipelineStage::where('agency_id', $offer->agency_id)
                ->where('is_won', true)
                ->first();

            if ($acceptedStage) {
                $deal->update([
                    'pipeline_stage_id' => $acceptedStage->id,
                ]);
                
                // Recalculate momentum
                try {
                    app(\App\Application\CRM\Actions\CalculateDealMomentumAction::class)->execute($deal);
                } catch (\Exception $e) {
                    Log::error("Failed to calculate deal momentum during offer acceptance: " . $e->getMessage());
                }
            }

            return $transaction;
        });
    }
}
