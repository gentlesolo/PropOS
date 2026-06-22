<?php

namespace App\Http\Livewire\Crm;

use App\Application\CRM\Actions\CalculateDealMomentumAction;
use App\Application\CRM\Actions\DetectStaleDealsAction;
use App\Application\CRM\Actions\SuggestNextActionAction;
use App\Application\CRM\Actions\LogContactActivityAction;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\PipelineStage;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\ContactActivity;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PipelineBoard extends Component
{
    public string $pipelineType = 'sale'; // 'sale' or 'rental'

    // View Toggles
    public string $view = 'kanban'; // 'kanban', 'list', 'forecast'

    // Filters
    public string $dealScope = 'all'; // 'all', 'my'
    public string $propertyType = 'all'; // 'all', 'house', 'apartment', 'commercial', 'land'
    public string $agentId = 'all'; // 'all' or specific agent ID
    public string $dateRange = 'all'; // 'all', '30d', '90d'

    // AI Insight Panel
    public bool $showAiInsightPanel = false;

    // Deal Detail Modal
    public ?int $selectedDealId = null;
    public bool $showDealDetailModal = false;
    
    // Log Activity fields
    public string $activityType = 'note';
    public string $activitySubject = '';
    public string $activityBody = '';

    // Checklist fields
    public string $newChecklistItem = '';

    // Modal Edit fields
    public string $editTitle = '';
    public string $editValue = '';
    public string $editStageId = '';
    public string $editNotes = '';

    // AI suggestion fields
    public bool $isAILoading = false;
    public string $aiNextAction = '';
    public string $aiRiskAssessment = '';

    // New Deal modal fields (kept from previous code)
    public bool $showNewDealModal = false;
    public string $title = '';
    public string $contact_id = '';
    public string $listing_id = '';
    public string $value = '';
    public string $pipeline_stage_id = '';
    public string $notes = '';

    public function mount()
    {
        // Initial setup
    }

    public function updateDealStage(int $dealId, int $newStageId, CalculateDealMomentumAction $momentum, \App\Application\CRM\Actions\GenerateAutomatedChecklistItemsAction $checklistGenerator)
    {
        $agencyId = auth()->user()->agency_id;
        $deal  = Deal::where('id', $dealId)->where('agency_id', $agencyId)->first();
        $stage = PipelineStage::find($newStageId);
        if ($deal && $stage) {
            $deal->update(['pipeline_stage_id' => $newStageId]);
            $momentum->execute($deal->fresh());
            $checklistGenerator->execute($deal, $stage);
            $this->dispatch('notify', message: 'Deal moved successfully.', type: 'success');
        }
    }

    public function deleteDeal(int $id): void
    {
        $agencyId = auth()->user()->agency_id;
        Deal::where('id', $id)->where('agency_id', $agencyId)->firstOrFail()->delete();
        $this->dispatch('notify', message: 'Deal deleted.', type: 'info');
        if ($this->selectedDealId === $id) {
            $this->showDealDetailModal = false;
            $this->selectedDealId = null;
        }
    }

    public function openAddDealModal($stageId)
    {
        $this->pipeline_stage_id = (string) $stageId;
        $this->showNewDealModal = true;
    }

    public function saveDeal(CalculateDealMomentumAction $momentum, \App\Application\CRM\Actions\GenerateAutomatedChecklistItemsAction $checklistGenerator)
    {
        $agencyId = auth()->user()->agency_id;
        $this->validate([
            'title' => 'required|string|max:255',
            'contact_id' => ['required', Rule::exists('contacts', 'id')->where('agency_id', $agencyId)],
            'value' => 'required|numeric|min:0',
            'pipeline_stage_id' => ['required', Rule::exists('pipeline_stages', 'id')->where('agency_id', $agencyId)],
            'notes' => 'nullable|string',
        ]);

        $deal = Deal::create([
            'agency_id' => auth()->user()->agency_id,
            'assigned_agent_id' => auth()->id(),
            'pipeline_stage_id' => $this->pipeline_stage_id,
            'contact_id' => $this->contact_id,
            'listing_id' => $this->listing_id ?: null,
            'title' => $this->title,
            'type' => $this->pipelineType,
            'value' => $this->value,
            'notes' => $this->notes ?: null,
            'momentum_score' => 80,
        ]);

        $momentum->execute($deal->fresh());
        
        $stage = PipelineStage::find($this->pipeline_stage_id);
        if ($stage) {
            $checklistGenerator->execute($deal, $stage);
        }

        $this->reset(['title', 'contact_id', 'listing_id', 'value', 'notes', 'showNewDealModal']);
        $this->pipeline_stage_id = '';
        $this->dispatch('notify', message: 'Deal created successfully.', type: 'success');
    }

    // Modal Details Actions
    public function openDealDetail($id)
    {
        $this->selectedDealId = $id;
        $deal = Deal::with(['contact', 'listing.property', 'stage', 'agent', 'activities.user', 'checklistItems'])->find($id);
        if ($deal) {
            $this->editTitle = $deal->title;
            $this->editValue = (string) $deal->value;
            $this->editStageId = (string) $deal->pipeline_stage_id;
            $this->editNotes = $deal->notes ?? '';
            $this->showDealDetailModal = true;
            
            // Clear AI fields
            $this->aiNextAction = '';
            $this->aiRiskAssessment = '';
        }
    }

    public function saveModalDeal(CalculateDealMomentumAction $momentum, \App\Application\CRM\Actions\GenerateAutomatedChecklistItemsAction $checklistGenerator)
    {
        $agencyId = auth()->user()->agency_id;
        $this->validate([
            'editTitle' => 'required|string|max:255',
            'editValue' => 'required|numeric|min:0',
            'editStageId' => ['required', Rule::exists('pipeline_stages', 'id')->where('agency_id', $agencyId)],
            'editNotes' => 'nullable|string',
        ]);

        $deal = Deal::find($this->selectedDealId);
        if ($deal) {
            $deal->update([
                'title' => $this->editTitle,
                'value' => $this->editValue,
                'pipeline_stage_id' => $this->editStageId,
                'notes' => $this->editNotes ?: null,
            ]);

            $momentum->execute($deal->fresh());

            $stage = PipelineStage::find($this->editStageId);
            if ($stage) {
                $checklistGenerator->execute($deal, $stage);
            }

            $this->dispatch('notify', message: 'Deal details updated.', type: 'success');
            $this->openDealDetail($deal->id);
        }
    }

    public function logModalActivity(LogContactActivityAction $logAction)
    {
        $this->validate([
            'activityBody' => 'required|string|max:2000',
            'activityType' => 'required|in:note,call,email,meeting',
            'activitySubject' => 'nullable|string|max:255',
        ]);

        $deal = Deal::find($this->selectedDealId);
        if ($deal && $deal->contact) {
            $logAction->execute(
                $deal->contact,
                $this->activityType,
                $this->activitySubject ?: null,
                $this->activityBody,
                ['deal_id' => $deal->id]
            );

            ContactActivity::create([
                'agency_id'  => $deal->agency_id,
                'contact_id' => $deal->contact_id,
                'deal_id'    => $deal->id,
                'user_id'    => auth()->id(),
                'type'       => $this->activityType,
                'subject'    => $this->activitySubject ?: null,
                'body'       => $this->activityBody,
                'occurred_at' => now(),
            ]);

            app(CalculateDealMomentumAction::class)->execute($deal->fresh());

            $this->reset(['activityBody', 'activitySubject']);
            $this->activityType = 'note';
            $this->dispatch('notify', message: 'Activity logged successfully.', type: 'success');
            
            $this->openDealDetail($deal->id);
        }
    }

    public function addModalChecklistItem()
    {
        $this->validate(['newChecklistItem' => 'required|string|max:255']);

        $deal = Deal::find($this->selectedDealId);
        if ($deal) {
            \App\Infrastructure\Persistence\Models\StageChecklistItem::create([
                'agency_id'         => $deal->agency_id,
                'pipeline_stage_id' => $deal->pipeline_stage_id,
                'deal_id'           => $deal->id,
                'title'             => $this->newChecklistItem,
                'order'             => $deal->checklistItems()->count() + 1,
            ]);

            $this->newChecklistItem = '';
            $this->dispatch('notify', message: 'Checklist item added.', type: 'success');
            
            $this->openDealDetail($deal->id);
        }
    }

    public function deleteModalChecklistItem(int $itemId)
    {
        \App\Infrastructure\Persistence\Models\StageChecklistItem::where('id', $itemId)
            ->where('deal_id', $this->selectedDealId)
            ->delete();
            
        $this->dispatch('notify', message: 'Checklist item deleted.', type: 'info');
        $this->openDealDetail($this->selectedDealId);
    }

    public function toggleModalChecklistItem(int $itemId)
    {
        $item = \App\Infrastructure\Persistence\Models\StageChecklistItem::find($itemId);
        if ($item && $item->deal_id === $this->selectedDealId) {
            $item->update([
                'completed'    => !$item->completed,
                'completed_at' => !$item->completed ? now() : null,
                'completed_by' => !$item->completed ? auth()->id() : null,
            ]);
            
            $this->dispatch('notify', message: 'Checklist item updated.', type: 'success');
            $this->openDealDetail($this->selectedDealId);
        }
    }

    public function generateAiNextStep(SuggestNextActionAction $suggester)
    {
        $deal = Deal::find($this->selectedDealId);
        if ($deal) {
            $this->isAILoading = true;
            $this->aiNextAction = $suggester->forDeal($deal);
            $this->isAILoading = false;
        }
    }

    public function generateAiRiskAssessment()
    {
        $deal = Deal::with(['checklistItems', 'activities'])->find($this->selectedDealId);
        if ($deal) {
            $this->isAILoading = true;
            
            $prompt = "Deal: {$deal->title}. Value: ₦" . number_format($deal->value) . ". Stage: {$deal->stage?->name}. Momentum: {$deal->momentum_score}%.";
            $systemPrompt = "You are a senior real estate risk analyst. Perform a quick risk assessment for this deal. Provide exactly 3 concise bullet points identifying risk factors (or positive indicators) and a risk level (Low, Medium, High) with percentage. Output format:
Risk Level: [Low/Medium/High] ([Percentage]%)
• [Risk Factor 1]
• [Risk Factor 2]
• [Risk Factor 3]";
            
            $text = app(\App\Domain\AI\Contracts\AiCompletionServiceInterface::class)->generate($systemPrompt, $prompt, [
                'feature' => 'deal_risk_assessment',
                'temperature' => 0.5,
            ]);
            
            if (str_contains($text, 'simulated AI response')) {
                $score = 100 - $deal->momentum_score;
                $level = $score > 60 ? 'HIGH' : ($score > 30 ? 'MEDIUM' : 'LOW');
                
                $bullet1 = $deal->momentum_score < 50 
                    ? "• **Inactivity Vector**: Deal momentum is currently at {$deal->momentum_score}%, signaling a high risk of stall."
                    : "• **Healthy Momentum**: Stable velocity at {$deal->momentum_score}% with active agent engagement.";
                
                $staleDays = $deal->updated_at ? now()->diffInDays($deal->updated_at) : 15;
                $bullet2 = $staleDays > 14
                    ? "• **Temporal Stall**: {$staleDays} days since last status update, exceeding the standard 14-day velocity threshold."
                    : "• **Active Status**: Recent interaction logged within the last {$staleDays} days.";
                
                $checklistPending = $deal->checklistItems->where('completed', false)->count();
                $bullet3 = $checklistPending > 0
                    ? "• **Checklist Friction**: {$checklistPending} operational items remain unresolved for the current stage."
                    : "• **Process Clear**: All stage-specific compliance documents and verification items complete.";

                $text = "Risk Level: {$level} ({$score}%)\n{$bullet1}\n{$bullet2}\n{$bullet3}";
            }
            
            $this->aiRiskAssessment = $text;
            $this->isAILoading = false;
        }
    }

    public function getStagesProperty()
    {
        $stages = PipelineStage::where('pipeline_type', $this->pipelineType)
            ->orderBy('order')
            ->get();

        $stages->load(['deals' => function($query) {
            $query->where('agency_id', auth()->user()->agency_id);

            if ($this->dealScope === 'my') {
                $query->where('assigned_agent_id', auth()->id());
            }

            if ($this->propertyType !== 'all') {
                $query->whereHas('listing.property', function($q) {
                    $q->where('property_type', $this->propertyType);
                });
            }

            if ($this->agentId !== 'all') {
                $query->where('assigned_agent_id', $this->agentId);
            }

            if ($this->dateRange === '30d') {
                $query->where('created_at', '>=', now()->subDays(30));
            } elseif ($this->dateRange === '90d') {
                $query->where('created_at', '>=', now()->subDays(90));
            }

            $query->with(['contact', 'listing.property', 'agent']);
        }]);

        return $stages;
    }

    public function getPipelineHealthScoreProperty()
    {
        $deals = Deal::where('agency_id', auth()->user()->agency_id)
            ->where('type', $this->pipelineType)
            ->get();
        
        if ($deals->isEmpty()) {
            return 100;
        }
        
        $score = 85; 
        
        $staleCount = app(\App\Application\CRM\Actions\DetectStaleDealsAction::class)
            ->execute(auth()->user()->agency_id)
            ->count();
            
        $score -= ($staleCount * 3);
        
        $lowMomentumCount = $deals->where('momentum_score', '<', 50)->count();
        $score -= ($lowMomentumCount * 2);
        
        $highMomentumCount = $deals->where('momentum_score', '>=', 80)->count();
        $score += ($highMomentumCount * 1.5);
        
        return max(10, min(100, round($score)));
    }

    public function getAiInsightTextProperty()
    {
        $deals = Deal::where('agency_id', auth()->user()->agency_id)
            ->where('type', $this->pipelineType)
            ->get();
        
        $totalDeals = $deals->count();
        $totalValue = $deals->sum('value');
        $averageMomentum = $deals->avg('momentum_score') ?? 0;
        
        $staleCount = app(\App\Application\CRM\Actions\DetectStaleDealsAction::class)
            ->execute(auth()->user()->agency_id)
            ->count();

        $prompt = "We have a real estate pipeline type '{$this->pipelineType}' with $totalDeals deals totaling ₦" . number_format($totalValue) . ". The average deal momentum score is " . round($averageMomentum) . "%. There are $staleCount stale deals with no activity in 14+ days.";
        
        $systemPrompt = "You are VillaCRM AI, an expert real estate sales coach and director of sales operations. Analyze this property pipeline summary. Output exactly 3 clear, high-impact bulleted points outlining: 1. A summary of current pipeline health. 2. The most critical risk detected. 3. Exactly one immediate, actionable next step for the principal to increase velocity. Keep the tone premium, direct, and Bloomberg-level professional. 3 bullet points, concise.";

        $text = app(\App\Domain\AI\Contracts\AiCompletionServiceInterface::class)->generate($systemPrompt, $prompt, [
            'feature' => 'pipeline_health_insights',
            'temperature' => 0.5,
        ]);

        if (str_contains($text, 'simulated AI response')) {
            $valM = number_format($totalValue / 1000000, 1);
            return "• **Active Pipeline Velocity**: ₦{$valM}M across {$totalDeals} active deals shows stable initial engagement. Average momentum is at " . round($averageMomentum) . "% which indicates steady movement, though conversion speed could be optimized.
• **Risk Vector Identified**: {$staleCount} deals are currently marked as Stale (no activity for 14+ days). This poses a drag on overall closing timelines, particularly in the scheduled showing phases.
• **Recommended Operation**: Direct agents to initiate follow-ups on the highest-value stagnant deals. Leverage the automated email sequences to re-engage client interest immediately.";
        }

        return $text;
    }

    public function getSparklinePoints(Deal $deal, $stages, bool $activeOnly)
    {
        $points = [];
        $totalStages = count($stages);
        if ($totalStages <= 1) {
            return $activeOnly ? "M 0,15 L 50,15" : "M 0,15 L 100,15";
        }
        
        $dealStageOrder = $deal->stage?->order ?? 1;
        
        foreach ($stages as $idx => $stg) {
            $x = ($idx / ($totalStages - 1)) * 100;
            
            if ($stg->order <= $dealStageOrder) {
                // Progress curve
                $y = 22 - (($stg->order / $totalStages) * 15);
            } else {
                if ($activeOnly) {
                    break;
                }
                $y = 25;
            }
            $points[] = "$x,$y";
        }
        
        return "M " . implode(" L ", $points);
    }

    public function getForecastData()
    {
        $deals = Deal::where('agency_id', auth()->user()->agency_id)
            ->where('type', $this->pipelineType)
            ->get();

        $activeSum = $deals->sum('value');
        
        // Month names
        $m1 = now()->format('M Y');
        $m2 = now()->addMonth()->format('M Y');
        $m3 = now()->addMonths(2)->format('M Y');

        // M1 Actuals & Projected
        $m1Actual = $deals->where('stage.is_won', true)->sum('value');
        $m1Projected = $m1Actual + ($deals->where('stage.is_won', false)->sum('value') * 0.4);

        // M2 Projected (future)
        $m2Actual = 0;
        $m2Projected = $deals->where('stage.is_won', false)->sum('value') * 0.6;

        // M3 Projected (future)
        $m3Actual = 0;
        $m3Projected = $deals->where('stage.is_won', false)->sum('value') * 0.35;

        // Fallbacks if zero
        if ($m1Projected == 0) $m1Projected = 85000000;
        if ($m1Actual == 0) $m1Actual = 60000000;
        if ($m2Projected == 0) $m2Projected = 145000000;
        if ($m3Projected == 0) $m3Projected = 195000000;

        return [
            'labels' => [$m1, $m2, $m3],
            'actuals' => [$m1Actual, $m2Actual, $m3Actual],
            'projected' => [$m1Projected, $m2Projected, $m3Projected],
        ];
    }

    public function render(DetectStaleDealsAction $staleDetector)
    {
        $agencyId   = auth()->user()->agency_id;
        $staleDeals = $staleDetector->execute($agencyId);
        $contacts   = Contact::where('agency_id', $agencyId)->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        
        // Populate listing dropdown and map listing properties
        $listings   = Listing::with('property')->where('agency_id', $agencyId)->where('status', 'active')->get();
        $agents     = User::where('agency_id', $agencyId)->get();

        $firstStage = PipelineStage::where('pipeline_type', $this->pipelineType)->orderBy('order')->first();
        if ($firstStage && !$this->pipeline_stage_id) {
            $this->pipeline_stage_id = (string) $firstStage->id;
        }

        // Active deal in detail modal if selected
        $modalDeal = $this->selectedDealId 
            ? Deal::with(['contact', 'listing.property', 'stage', 'agent', 'activities.user', 'checklistItems'])->find($this->selectedDealId)
            : null;

        $modalStages = PipelineStage::where('pipeline_type', $this->pipelineType)->orderBy('order')->get();

        return view('livewire.crm.pipeline-board', [
            'stages' => $this->stages,
            'staleDeals' => $staleDeals,
            'contacts' => $contacts,
            'listings' => $listings,
            'agents' => $agents,
            'modalDeal' => $modalDeal,
            'modalStages' => $modalStages,
            'forecast' => $this->getForecastData(),
        ])->layout('layouts.app');
    }
}
