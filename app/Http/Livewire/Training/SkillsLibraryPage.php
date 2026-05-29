<?php

namespace App\Http\Livewire\Training;

use App\Infrastructure\Persistence\Models\TrainingModule;
use App\Infrastructure\Persistence\Models\TrainingProgress;
use Livewire\Component;

class SkillsLibraryPage extends Component
{
    public string $activeCategory = 'all';
    public ?int $openModuleId = null;

    public function getModulesProperty()
    {
        $userId = auth()->id();

        return TrainingModule::where('is_published', true)
            ->when($this->activeCategory !== 'all', fn($q) => $q->where('category', $this->activeCategory))
            ->orderBy('order')
            ->get()
            ->map(function (TrainingModule $module) use ($userId) {
                $progress = TrainingProgress::where('user_id', $userId)
                    ->where('module_id', $module->id)
                    ->first();

                $module->user_progress = $progress?->progress_pct ?? 0;
                $module->user_status = $progress?->status ?? 'not_started';
                $module->user_score = $progress?->score;
                return $module;
            });
    }

    public function openModule(int $moduleId): void
    {
        $this->openModuleId = $this->openModuleId === $moduleId ? null : $moduleId;

        if ($this->openModuleId === $moduleId && auth()->check()) {
            $module = TrainingModule::find($moduleId);
            if ($module) {
                TrainingProgress::firstOrCreate(
                    ['user_id' => auth()->id(), 'module_id' => $moduleId],
                    ['status' => 'in_progress', 'progress_pct' => 10, 'started_at' => now()]
                );
            }
        }
    }

    public function markComplete(int $moduleId): void
    {
        TrainingProgress::updateOrCreate(
            ['user_id' => auth()->id(), 'module_id' => $moduleId],
            [
                'progress_pct' => 100,
                'status' => 'completed',
                'started_at' => now()->subMinutes(rand(5, 30)),
                'completed_at' => now(),
            ]
        );
        $this->dispatch('notify', message: 'Module marked as complete!', type: 'success');
    }

    public function render()
    {
        $modules = $this->modules;
        $completed = $modules->where('user_status', 'completed')->count();
        $total = $modules->count();
        $overallPct = $total > 0 ? round(($completed / $total) * 100) : 0;

        return view('livewire.training.skills-library-page', compact('modules', 'overallPct'))
            ->layout('layouts.app');
    }
}
