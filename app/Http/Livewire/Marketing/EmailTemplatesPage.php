<?php

namespace App\Http\Livewire\Marketing;

use App\Infrastructure\Persistence\Models\EmailTemplate;
use Livewire\Component;

class EmailTemplatesPage extends Component
{
    public bool $showCreateForm = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $slug = '';
    public string $subject = '';
    public string $body_html = '';
    public string $category = 'system';

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'slug', 'subject', 'body_html', 'category']);
        $this->showCreateForm = true;
    }

    public function openEdit(int $id): void
    {
        $template = EmailTemplate::findOrFail($id);
        $this->editingId = $id;
        $this->name = $template->name;
        $this->slug = $template->slug;
        $this->subject = $template->subject;
        $this->body_html = $template->body_html;
        $this->category = $template->category;
        $this->showCreateForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'category' => 'required|in:lead,listing,offer,transaction,lease,marketing,system',
        ]);

        $this->slug = $this->editingId
            ? $this->slug
            : \Illuminate\Support\Str::slug($this->name) . '-' . now()->timestamp;

        $data = [
            'agency_id' => auth()->user()->agency_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'subject' => $this->subject,
            'body_html' => $this->body_html,
            'category' => $this->category,
        ];

        if ($this->editingId) {
            EmailTemplate::findOrFail($this->editingId)->update($data);
            $msg = 'Template updated.';
        } else {
            EmailTemplate::create($data);
            $msg = 'Template created.';
        }

        $this->reset(['showCreateForm', 'editingId', 'name', 'slug', 'subject', 'body_html', 'category']);
        $this->dispatch('notify', message: $msg, type: 'success');
    }

    public function toggleActive(int $id): void
    {
        $template = EmailTemplate::findOrFail($id);
        $template->update(['is_active' => !$template->is_active]);
    }

    public function delete(int $id): void
    {
        EmailTemplate::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Template deleted.', type: 'success');
    }

    public function render()
    {
        $templates = EmailTemplate::orderBy('category')->orderBy('name')->get();
        $grouped = $templates->groupBy('category');

        return view('livewire.marketing.email-templates-page', compact('templates', 'grouped'))
            ->layout('layouts.app');
    }
}
