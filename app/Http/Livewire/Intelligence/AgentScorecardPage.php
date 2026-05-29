<?php

namespace App\Http\Livewire\Intelligence;

use App\Domain\AI\Contracts\AiCompletionServiceInterface;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Viewing;
use Carbon\Carbon;
use Livewire\Component;

class AgentScorecardPage extends Component
{
    public string $timeframe = 'month';
    public bool $generatingAnalysis = false;
    public array $aiInsights = [];

    public function mount()
    {
        $this->aiInsights = $this->buildDefaultInsights();
    }

    public function updatedTimeframe(): void
    {
        $this->aiInsights = $this->buildDefaultInsights();
    }

    public function generateAnalysis(AiCompletionServiceInterface $ai): void
    {
        $this->generatingAnalysis = true;
        $metrics = $this->getPerformanceMetrics();

        $prompt = implode("\n", [
            "Agent performance data ({$this->timeframe}):",
            "- Won Revenue: ₦" . number_format($metrics['won_value']),
            "- Deals in pipeline: {$metrics['total_deals']} ({$metrics['won_deals']} won)",
            "- Conversion rate: {$metrics['conversion_rate']}%",
            "- Viewings completed: {$metrics['viewings_completed']}",
            "- New leads: {$metrics['new_leads']}",
            "Provide 3 specific, actionable coaching insights. Each should be one sentence. Return as a JSON array of strings.",
        ]);

        $raw = $ai->generate(
            "You are a high-performance real estate sales coach. Give concise, data-driven coaching insights.",
            $prompt
        );

        $parsed = json_decode($raw, true);
        $this->aiInsights = is_array($parsed) ? $parsed : $this->buildDefaultInsights($metrics);
        $this->generatingAnalysis = false;
    }

    public function getPerformanceMetrics(): array
    {
        $agentId = auth()->id();
        $queryStart = match ($this->timeframe) {
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'quarter' => Carbon::now()->firstOfQuarter(),
            default => Carbon::now()->startOfYear(),
        };

        $deals = Deal::where('assigned_agent_id', $agentId)
            ->where('created_at', '>=', $queryStart)
            ->with('stage')
            ->get();

        $wonDeals = $deals->filter(fn($d) => $d->stage?->is_won);

        $viewingsCompleted = Viewing::where('assigned_agent_id', $agentId)
            ->where('created_at', '>=', $queryStart)
            ->where('status', 'completed')
            ->count();

        $newLeads = Contact::where('assigned_agent_id', $agentId)
            ->orWhere(fn($q) => $q->where('agency_id', auth()->user()->agency_id)
                ->where('created_at', '>=', $queryStart))
            ->where('created_at', '>=', $queryStart)
            ->count();

        // Monthly chart data (last 12 weeks for chart)
        $chartData = [];
        for ($i = 13; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            $chartData[] = [
                'label' => $weekStart->format('d M'),
                'deals' => Deal::where('assigned_agent_id', $agentId)->whereBetween('created_at', [$weekStart, $weekEnd])->count(),
                'viewings' => Viewing::where('assigned_agent_id', $agentId)->whereBetween('scheduled_at', [$weekStart, $weekEnd])->count(),
            ];
        }

        return [
            'total_deals' => $deals->count(),
            'won_deals' => $wonDeals->count(),
            'pipeline_value' => (float) $deals->sum('value'),
            'won_value' => (float) $wonDeals->sum('value'),
            'viewings_completed' => $viewingsCompleted,
            'new_leads' => $newLeads,
            'conversion_rate' => $deals->count() > 0 ? round(($wonDeals->count() / $deals->count()) * 100) : 0,
            'chart_data' => $chartData,
        ];
    }

    private function buildDefaultInsights(array $metrics = []): array
    {
        if (empty($metrics)) {
            return [
                'Log in to your first deals to see personalized coaching.',
                'Track viewings and follow-ups consistently to improve your conversion rate.',
                'Click "Generate Full Analysis" for AI-powered coaching based on your live data.',
            ];
        }

        $insights = [];
        if ($metrics['conversion_rate'] > 0) {
            $insights[] = "Your {$metrics['conversion_rate']}% conversion rate " . ($metrics['conversion_rate'] >= 20 ? 'is above average — keep up the strong closing discipline.' : 'has room to grow — focus on qualifying leads earlier in the pipeline.');
        }
        if ($metrics['viewings_completed'] > 0) {
            $insights[] = "You completed {$metrics['viewings_completed']} viewings this {$this->timeframe}. " . ($metrics['viewings_completed'] >= 5 ? 'Great activity level.' : 'Aim for at least 5 per month to maintain pipeline velocity.');
        }
        if ($metrics['won_value'] > 0) {
            $insights[] = 'Won revenue this ' . $this->timeframe . ': ₦' . number_format($metrics['won_value']) . '. Focus your energy on the deals closest to closing.';
        }
        if (empty($insights)) {
            $insights = ['No deal activity yet this period. Start logging contacts and creating deals to see coaching insights.'];
        }
        return $insights;
    }

    public function render()
    {
        return view('livewire.intelligence.agent-scorecard-page', [
            'metrics' => $this->getPerformanceMetrics(),
        ])->layout('layouts.app');
    }
}
