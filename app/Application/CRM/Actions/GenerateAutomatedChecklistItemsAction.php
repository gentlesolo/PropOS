<?php

namespace App\Application\CRM\Actions;

use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\PipelineStage;
use App\Infrastructure\Persistence\Models\StageChecklistItem;

class GenerateAutomatedChecklistItemsAction
{
    /**
     * Generate automated checklist items for a deal based on its stage.
     */
    public function execute(Deal $deal, PipelineStage $stage): void
    {
        $stageName = strtolower($stage->name);

        $defaultTasks = [];

        if (str_contains($stageName, 'inquiry') || str_contains($stageName, 'lead')) {
            $defaultTasks = [
                'Verify contact details',
                'Identify buyer/seller requirements',
            ];
        } elseif (str_contains($stageName, 'qualified') || str_contains($stageName, 'appointment')) {
            $defaultTasks = [
                'Conduct face-to-face consultation',
                'Prepare and send custom CMA report',
            ];
        } elseif (str_contains($stageName, 'viewing') || str_contains($stageName, 'visit')) {
            $defaultTasks = [
                'Send viewing confirmation to client',
                'Prepare property brochures and marketing pack',
            ];
        } elseif (str_contains($stageName, 'offer made') || (str_contains($stageName, 'offer') && !str_contains($stageName, 'accept'))) {
            $defaultTasks = [
                'Verify buyer proof of funds',
                'Present formal offer to seller',
            ];
        } elseif (str_contains($stageName, 'negotiat')) {
            $defaultTasks = [
                'Draft counter-offer terms',
                'Conduct negotiation meeting',
            ];
        } elseif (str_contains($stageName, 'accept') || str_contains($stageName, 'contract') || str_contains($stageName, 'pending')) {
            $defaultTasks = [
                'Order home inspection',
                'Send disclosures to all parties',
                'Collect earnest money deposit',
            ];
        } elseif (str_contains($stageName, 'close') || $stage->is_won) {
            $defaultTasks = [
                'Schedule closing meeting',
                'Deliver keys to buyer',
                'Request testimonial review',
            ];
        } elseif (str_contains($stageName, 'lost') || $stage->is_lost) {
            $defaultTasks = [
                'Archive communications',
                'Schedule follow-up call in 6 months',
            ];
        }

        // Only create tasks that don't already exist for this deal at this stage
        foreach ($defaultTasks as $index => $taskTitle) {
            $exists = StageChecklistItem::where('deal_id', $deal->id)
                ->where('pipeline_stage_id', $stage->id)
                ->where('title', $taskTitle)
                ->exists();

            if (!$exists) {
                StageChecklistItem::create([
                    'agency_id' => $deal->agency_id,
                    'pipeline_stage_id' => $stage->id,
                    'deal_id' => $deal->id,
                    'title' => $taskTitle,
                    'order' => $index + 1,
                    'completed' => false,
                ]);
            }
        }
    }
}
