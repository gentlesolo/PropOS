<?php

namespace App\Http\Livewire\Training;

use App\Infrastructure\Persistence\Models\TrainingModule;
use App\Infrastructure\Persistence\Models\TrainingProgress;
use App\Infrastructure\Persistence\Models\User;
use Livewire\Component;

class TrainingDashboardPage extends Component
{
    public function render()
    {
        $userId = auth()->id();
        $agencyId = auth()->user()->agency_id;

        // My progress
        $allModules = TrainingModule::where('is_published', true)->get();
        $myProgress = TrainingProgress::where('user_id', $userId)->get();
        $completed = $myProgress->where('status', 'completed')->count();
        $total = $allModules->count();
        $mandatory = $allModules->where('is_mandatory', true);
        $mandatoryCompleted = $myProgress->whereIn('module_id', $mandatory->pluck('id'))->where('status', 'completed')->count();

        // Recent completions
        $recentCompletions = TrainingProgress::with('module')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->limit(5)
            ->get();

        // Next recommended module
        $completedIds = $myProgress->pluck('module_id');
        $nextModule = TrainingModule::where('is_published', true)
            ->whereNotIn('id', $completedIds)
            ->orderBy('is_mandatory', 'desc')
            ->orderBy('order')
            ->first();

        // Team leaderboard
        $leaderboard = User::where('agency_id', $agencyId)
            ->where('status', 'active')
            ->get()
            ->map(function (User $user) use ($total) {
                $done = TrainingProgress::where('user_id', $user->id)->where('status', 'completed')->count();
                $avg = TrainingProgress::where('user_id', $user->id)->whereNotNull('score')->avg('score') ?? 0;
                return [
                    'user' => $user,
                    'completed' => $done,
                    'pct' => $total > 0 ? round(($done / $total) * 100) : 0,
                    'avg_score' => round($avg),
                ];
            })
            ->sortByDesc('pct')
            ->values();

        return view('livewire.training.training-dashboard-page', compact(
            'allModules', 'completed', 'total', 'mandatory', 'mandatoryCompleted',
            'recentCompletions', 'nextModule', 'leaderboard'
        ))->layout('layouts.app');
    }
}
