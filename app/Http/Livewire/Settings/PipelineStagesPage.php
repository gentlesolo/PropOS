<?php

namespace App\Http\Livewire\Settings;

use App\Infrastructure\Persistence\Models\PipelineStage;
use Livewire\Component;

class PipelineStagesPage extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $pipeline_type = 'sale';
    public int $order = 1;
    public bool $is_won = false;
    public bool $is_lost = false;

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'pipeline_type' => 'required|in:sale,rental',
            'order' => 'required|integer|min:1',
        ]);

        $data = [
            'agency_id' => auth()->user()->agency_id,
            'name' => $this->name,
            'pipeline_type' => $this->pipeline_type,
            'order' => $this->order,
            'is_won' => $this->is_won,
            'is_lost' => $this->is_lost,
        ];

        // Ensure only one won/lost stage is marked per pipeline type if needed,
        // or just let the database handle it. Let's make sure if this is won, we reset other won stages.
        if ($this->is_won) {
            PipelineStage::where('pipeline_type', $this->pipeline_type)
                ->where('is_won', true)
                ->update(['is_won' => false]);
        }
        if ($this->is_lost) {
            PipelineStage::where('pipeline_type', $this->pipeline_type)
                ->where('is_lost', true)
                ->update(['is_lost' => false]);
        }

        if ($this->editingId) {
            PipelineStage::withoutGlobalScope(\App\Infrastructure\Persistence\Scopes\BelongsToAgencyScope::class)
                ->findOrFail($this->editingId)
                ->update($data);
            $msg = 'Pipeline stage updated successfully.';
        } else {
            PipelineStage::create($data);
            $msg = 'Pipeline stage created successfully.';
        }

        $this->reset(['showForm', 'editingId', 'name', 'pipeline_type', 'is_won', 'is_lost']);
        $this->order = PipelineStage::count() + 1;
        $this->dispatch('notify', message: $msg, type: 'success');
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'is_won', 'is_lost']);
        $this->order = PipelineStage::where('pipeline_type', $this->pipeline_type)->count() + 1;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $stage = PipelineStage::withoutGlobalScope(\App\Infrastructure\Persistence\Scopes\BelongsToAgencyScope::class)
            ->findOrFail($id);

        $this->editingId = $id;
        $this->name = $stage->name;
        $this->pipeline_type = $stage->pipeline_type;
        $this->order = $stage->order;
        $this->is_won = (bool) $stage->is_won;
        $this->is_lost = (bool) $stage->is_lost;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        $stage = PipelineStage::withoutGlobalScope(\App\Infrastructure\Persistence\Scopes\BelongsToAgencyScope::class)
            ->findOrFail($id);

        if ($stage->deals()->count() > 0) {
            $this->dispatch('notify', message: 'Cannot delete stage with active deals linked to it.', type: 'error');
            return;
        }

        $stage->delete();
        $this->dispatch('notify', message: 'Pipeline stage deleted.', type: 'success');
    }

    public function moveUp(int $id): void
    {
        $stage = PipelineStage::withoutGlobalScope(\App\Infrastructure\Persistence\Scopes\BelongsToAgencyScope::class)
            ->findOrFail($id);

        if ($stage->order > 1) {
            $prev = PipelineStage::where('pipeline_type', $stage->pipeline_type)
                ->where('order', $stage->order - 1)
                ->first();

            if ($prev) {
                $prev->update(['order' => $stage->order]);
            }
            $stage->update(['order' => $stage->order - 1]);
        }
    }

    public function moveDown(int $id): void
    {
        $stage = PipelineStage::withoutGlobalScope(\App\Infrastructure\Persistence\Scopes\BelongsToAgencyScope::class)
            ->findOrFail($id);

        $max = PipelineStage::where('pipeline_type', $stage->pipeline_type)->max('order');
        if ($stage->order < $max) {
            $next = PipelineStage::where('pipeline_type', $stage->pipeline_type)
                ->where('order', $stage->order + 1)
                ->first();

            if ($next) {
                $next->update(['order' => $stage->order]);
            }
            $stage->update(['order' => $stage->order + 1]);
        }
    }

    public function render()
    {
        $stages = PipelineStage::orderBy('pipeline_type')
            ->orderBy('order')
            ->get();

        return view('livewire.settings.pipeline-stages-page', compact('stages'))
            ->layout('layouts.app');
    }
}
