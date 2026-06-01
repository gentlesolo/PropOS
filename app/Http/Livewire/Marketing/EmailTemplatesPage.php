<?php

namespace App\Http\Livewire\Marketing;

use App\Infrastructure\Persistence\Models\EmailTemplate;
use Illuminate\Support\Str;
use Livewire\Component;

class EmailTemplatesPage extends Component
{
    // ── Filters ───────────────────────────────────────────────────────────────
    public string $search          = '';
    public string $categoryFilter  = '';

    // ── Detail / preview panel ────────────────────────────────────────────────
    public bool $showPreview     = false;
    public ?int $previewId       = null;

    // ── Create / Edit form ────────────────────────────────────────────────────
    public bool   $showForm      = false;
    public ?int   $editingId     = null;

    public string $name          = '';
    public string $slug          = '';
    public string $subject       = '';
    public string $body_html     = '';
    public string $body_text     = '';
    public string $category      = 'system';
    public string $variables_raw = '';   // comma-separated input → array on save

    public function updatingSearch(): void       { /* no pagination here */ }
    public function updatingCategoryFilter(): void { /* no pagination here */ }

    // ── Create ────────────────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'slug', 'subject', 'body_html', 'body_text', 'variables_raw']);
        $this->category  = 'system';
        $this->showForm  = true;
        $this->showPreview = false;
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function openEdit(int $id): void
    {
        $template = $this->scopedTemplate($id);

        $this->editingId      = $template->id;
        $this->name           = $template->name;
        $this->slug           = $template->slug;
        $this->subject        = $template->subject;
        $this->body_html      = $template->body_html;
        $this->body_text      = $template->body_text ?? '';
        $this->category       = $template->category;
        $this->variables_raw  = implode(', ', $template->available_variables ?? []);
        $this->showForm       = true;
        $this->showPreview    = false;
    }

    public function cancelForm(): void
    {
        $this->reset(['showForm', 'editingId', 'name', 'slug', 'subject',
            'body_html', 'body_text', 'category', 'variables_raw']);
    }

    // ── Save ──────────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->validate([
            'name'     => 'required|string|max:255',
            'subject'  => 'required|string|max:255',
            'body_html'=> 'required|string',
            'category' => 'required|in:lead,listing,offer,transaction,lease,marketing,system',
        ]);

        $agencyId  = auth()->user()->agency_id;
        $variables = array_values(array_filter(array_map('trim', explode(',', $this->variables_raw))));

        if ($this->editingId) {
            $slug = $this->slug;
        } else {
            $base = Str::slug($this->name);
            $slug = EmailTemplate::where('agency_id', $agencyId)->where('slug', $base)->exists()
                ? $base . '-' . now()->timestamp
                : $base;
        }

        $data = [
            'agency_id'           => $agencyId,
            'name'                => $this->name,
            'slug'                => $slug,
            'subject'             => $this->subject,
            'body_html'           => $this->body_html,
            'body_text'           => $this->body_text ?: null,
            'category'            => $this->category,
            'available_variables' => $variables ?: null,
        ];

        if ($this->editingId) {
            $this->scopedTemplate($this->editingId)->update($data);
            $msg = 'Template updated.';
        } else {
            EmailTemplate::create($data);
            $msg = 'Template created.';
        }

        $this->cancelForm();
        $this->dispatch('notify', message: $msg, type: 'success');
    }

    // ── Preview ───────────────────────────────────────────────────────────────

    public function openPreview(int $id): void
    {
        $this->previewId   = $id;
        $this->showPreview = true;
        $this->showForm    = false;
    }

    public function closePreview(): void
    {
        $this->showPreview = false;
        $this->previewId   = null;
    }

    // ── Toggle active ─────────────────────────────────────────────────────────

    public function toggleActive(int $id): void
    {
        $template = $this->scopedTemplate($id);
        $template->update(['is_active' => ! $template->is_active]);
    }

    // ── Duplicate ─────────────────────────────────────────────────────────────

    public function duplicate(int $id): void
    {
        $source   = $this->scopedTemplate($id);
        $agencyId = auth()->user()->agency_id;
        $base     = $source->slug . '-copy';
        $slug     = EmailTemplate::where('agency_id', $agencyId)->where('slug', $base)->exists()
            ? $base . '-' . now()->timestamp
            : $base;

        EmailTemplate::create([
            'agency_id'           => $agencyId,
            'name'                => $source->name . ' (Copy)',
            'slug'                => $slug,
            'subject'             => $source->subject,
            'body_html'           => $source->body_html,
            'body_text'           => $source->body_text,
            'category'            => $source->category,
            'available_variables' => $source->available_variables,
            'is_active'           => false,
        ]);

        $this->dispatch('notify', message: 'Template duplicated as inactive draft.', type: 'success');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function delete(int $id): void
    {
        if ($this->previewId === $id) {
            $this->showPreview = false;
        }

        $this->scopedTemplate($id)->delete();
        $this->dispatch('notify', message: 'Template deleted.', type: 'success');
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function scopedTemplate(int $id): EmailTemplate
    {
        return EmailTemplate::where('id', $id)
            ->where('agency_id', auth()->user()->agency_id)
            ->firstOrFail();
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $templates = EmailTemplate::where('agency_id', $agencyId)
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('subject', 'like', "%{$this->search}%"))
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $grouped = $templates->groupBy('category');

        $previewTemplate = null;
        if ($this->showPreview && $this->previewId) {
            $previewTemplate = EmailTemplate::where('agency_id', $agencyId)->find($this->previewId);
        }

        $totalByCategory = EmailTemplate::where('agency_id', $agencyId)
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category');

        return view('livewire.marketing.email-templates-page',
            compact('templates', 'grouped', 'previewTemplate', 'totalByCategory'))
            ->layout('layouts.app');
    }
}
