<?php

namespace App\Http\Livewire;

use App\Infrastructure\Persistence\Models\ComplianceReminder;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\Inspection;
use App\Infrastructure\Persistence\Models\Lease;
use App\Infrastructure\Persistence\Models\Listing;
use App\Infrastructure\Persistence\Models\Property;
use App\Infrastructure\Persistence\Models\Deal;
use App\Infrastructure\Persistence\Models\Viewing;
use App\Infrastructure\Persistence\Models\Commission;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\PipelineStage;
use App\Infrastructure\Tenancy\TenantResolver;
use Livewire\Component;

class DashboardPage extends Component
{
    public string $viewMode = 'agent';

    public function mount()
    {
        $user = auth()->user();
        $this->viewMode = $user && $user->hasRole('principal') ? 'principal' : 'agent';
    }

    public function setViewMode(string $mode)
    {
        if (in_array($mode, ['agent', 'principal'])) {
            $this->viewMode = $mode;
        }
    }

    public function render()
    {
        $resolver = app(TenantResolver::class);
        $agency   = $resolver->getCurrentAgency();
        $user     = auth()->user();
        $agencyId = $user->agency_id;

        // --- AGENT VIEW METRICS ---
        $myPipelineValue = Deal::where('assigned_agent_id', $user->id)->sum('value');
        $activeLeadsCount = Contact::where('assigned_agent_id', $user->id)
            ->whereIn('status', ['new', 'active', 'qualified', 'nurturing'])
            ->count();
        $viewingsCount = Viewing::where('assigned_agent_id', $user->id)
            ->whereBetween('scheduled_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $commissionYtd = Commission::where('agent_id', $user->id)
            ->whereYear('paid_at', now()->year)
            ->sum('agent_commission');

        // --- PRINCIPAL VIEW METRICS ---
        $totalAgencyGci = Commission::whereYear('paid_at', now()->year)->sum('gross_commission');
        $activeListings = Listing::where('status', 'active')->count();
        $teamPerformanceScore = round(Deal::avg('momentum_score') ?? 84);
        
        $topAgentThisMonth = User::join('commissions', 'users.id', '=', 'commissions.agent_id')
            ->whereBetween('commissions.paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('users.first_name, users.last_name, SUM(commissions.gross_commission) as total_earned')
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->orderByDesc('total_earned')
            ->first();
        
        $topAgentName = $topAgentThisMonth ? $topAgentThisMonth->first_name . ' ' . $topAgentThisMonth->last_name : 'N/A';
        $topAgentVal = $topAgentThisMonth ? $topAgentThisMonth->total_earned : 0;

        $teamHeadcount = User::count();

        // --- ZONE 1 PRIORITIES (AGENT VIEW) ---
        $priorities = [
            (object)[
                'lead' => 'Nneka Obi',
                'action' => 'Schedule viewing for Ikoyi Penthouse',
                'urgency' => 'high',
                'badge_color' => 'bg-[#F43F5E]/10 text-[#F43F5E]'
            ],
            (object)[
                'lead' => 'Sipho Khumalo',
                'action' => 'Follow up on lease signature',
                'urgency' => 'medium',
                'badge_color' => 'bg-[#F59E0B]/10 text-[#F59E0B]'
            ],
            (object)[
                'lead' => 'David Kiprop',
                'action' => 'Submit compliance documents',
                'urgency' => 'low',
                'badge_color' => 'bg-[#10B981]/10 text-[#10B981]'
            ],
        ];

        // --- ZONE 3 SPLIT CONTENT: PIPELINE KANBAN STAGES ---
        $stages = PipelineStage::with(['deals.contact'])->orderBy('order')->get();

        // --- ZONE 3 SPLIT CONTENT: LEAD ACTIVITY FEED (AGENT VIEW) ---
        $recentViewings = Viewing::with(['contact', 'listing'])->latest()->limit(3)->get();
        $recentContacts = Contact::latest()->limit(3)->get();
        $recentDeals = Deal::with(['contact', 'stage'])->latest()->limit(2)->get();

        $activities = collect();
        foreach ($recentViewings as $viewing) {
            if ($viewing->contact) {
                $activities->push((object)[
                    'id' => 'v-' . $viewing->id,
                    'type' => 'viewing',
                    'contact_name' => $viewing->contact->name,
                    'contact_initials' => strtoupper(substr($viewing->contact->first_name, 0, 1) . substr($viewing->contact->last_name, 0, 1)),
                    'title' => 'Scheduled viewing',
                    'description' => 'Interested in ' . ($viewing->listing->title ?? 'Listing'),
                    'time_ago' => $viewing->created_at->diffForHumans(),
                    'border_color' => 'border-[#0ea5e9]'
                ]);
            }
        }
        foreach ($recentContacts as $contact) {
            $activities->push((object)[
                'id' => 'c-' . $contact->id,
                'type' => 'new_lead',
                'contact_name' => $contact->name,
                'contact_initials' => strtoupper(substr($contact->first_name, 0, 1) . substr($contact->last_name, 0, 1)),
                'title' => 'New inquiry',
                'description' => 'Registered from website lead capture',
                'time_ago' => $contact->created_at->diffForHumans(),
                'border_color' => 'border-[#10B981]'
            ]);
        }
        foreach ($recentDeals as $deal) {
            if ($deal->contact) {
                $activities->push((object)[
                    'id' => 'd-' . $deal->id,
                    'type' => 'deal_update',
                    'contact_name' => $deal->contact->name,
                    'contact_initials' => strtoupper(substr($deal->contact->first_name, 0, 1) . substr($deal->contact->last_name, 0, 1)),
                    'title' => 'Pipeline stage change',
                    'description' => 'Moved to ' . ($deal->stage->name ?? 'stage'),
                    'time_ago' => $deal->updated_at->diffForHumans(),
                    'border_color' => 'border-[#F59E0B]'
                ]);
            }
        }

        if ($activities->isEmpty()) {
            $activities = collect([
                (object)[
                    'id' => 1,
                    'type' => 'new_lead',
                    'contact_name' => 'Nneka Obi',
                    'contact_initials' => 'NO',
                    'title' => 'New lead captured',
                    'description' => 'Inquired about Lekki Phase 1 Penthouse',
                    'time_ago' => '10 mins ago',
                    'border_color' => 'border-[#10B981]'
                ],
                (object)[
                    'id' => 2,
                    'type' => 'viewing',
                    'contact_name' => 'Sipho Khumalo',
                    'contact_initials' => 'SK',
                    'title' => 'Viewing completed',
                    'description' => 'Reviewed Rosebank office spaces layout',
                    'time_ago' => '42 mins ago',
                    'border_color' => 'border-[#0ea5e9]'
                ],
                (object)[
                    'id' => 3,
                    'type' => 'deal_update',
                    'contact_name' => 'David Kiprop',
                    'contact_initials' => 'DK',
                    'title' => 'Offer submitted',
                    'description' => 'Offered KES 45M for Karen Heights Villa',
                    'time_ago' => '2 hours ago',
                    'border_color' => 'border-[#F59E0B]'
                ],
            ]);
        }
        $activities = $activities->take(5);

        // --- ZONE 3 SPLIT CONTENT: AGENT LEADERBOARD (PRINCIPAL VIEW) ---
        $leaderboardRaw = User::selectRaw('users.id, users.first_name, users.last_name, users.job_title, COALESCE(SUM(commissions.gross_commission), 0) as total_gci')
            ->leftJoin('commissions', 'users.id', '=', 'commissions.agent_id')
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.job_title')
            ->orderByDesc('total_gci')
            ->get();

        $maxGci = max(1.0, (float) $leaderboardRaw->max('total_gci'));
        $leaderboard = $leaderboardRaw->map(function($agent) use ($maxGci) {
            $agent->percentage = min(100, round(((float) $agent->total_gci / $maxGci) * 100));
            return $agent;
        });

        return view('livewire.dashboard-page', [
            'agency' => $agency,
            'user' => $user,
            'myPipelineValue' => $myPipelineValue,
            'activeLeadsCount' => $activeLeadsCount,
            'viewingsCount' => $viewingsCount,
            'commissionYtd' => $commissionYtd,
            'totalAgencyGci' => $totalAgencyGci,
            'activeListings' => $activeListings,
            'teamPerformanceScore' => $teamPerformanceScore,
            'topAgentName' => $topAgentName,
            'topAgentVal' => $topAgentVal,
            'teamHeadcount' => $teamHeadcount,
            'priorities' => $priorities,
            'stages' => $stages,
            'activities' => $activities,
            'leaderboard' => $leaderboard,
        ])->layout('layouts.app');
    }
}
