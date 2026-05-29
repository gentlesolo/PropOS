<?php

namespace App\Http\Livewire\Marketing;

use App\Infrastructure\ExternalServices\WhatsApp\WhatsAppApiClient;
use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\WhatsAppMessage;
use App\Infrastructure\Persistence\Models\WhatsAppTemplate;
use Livewire\Component;

class WhatsAppPage extends Component
{
    public string $activeTab = 'messages';

    // Compose
    public bool $showCompose = false;
    public string $compose_to = '';
    public string $compose_contact_id = '';
    public string $compose_body = '';
    public string $compose_template_id = '';

    // Templates
    public bool $showTemplateForm = false;
    public string $template_name = '';
    public string $template_body = '';
    public string $template_category = 'marketing';

    protected array $composeRules = [
        'compose_body' => 'required|string|max:1000',
        'compose_to' => 'required|string|max:20',
    ];

    public function sendMessage(WhatsAppApiClient $whatsApp): void
    {
        $this->validate($this->composeRules);

        $message = WhatsAppMessage::create([
            'agency_id'  => auth()->user()->agency_id,
            'contact_id' => $this->compose_contact_id ?: null,
            'template_id' => $this->compose_template_id ?: null,
            'to_number'  => $this->compose_to,
            'body'       => $this->compose_body,
            'direction'  => 'outbound',
            'status'     => 'queued',
        ]);

        $whatsApp->sendTextMessage($message);

        $this->reset(['compose_to', 'compose_body', 'compose_contact_id', 'compose_template_id', 'showCompose']);
        $this->dispatch('notify', message: 'Message sent.', type: 'success');
    }

    public function saveTemplate(): void
    {
        $this->validate([
            'template_name' => 'required|string|max:255',
            'template_body' => 'required|string|max:1000',
            'template_category' => 'required|in:marketing,utility,authentication',
        ]);

        WhatsAppTemplate::create([
            'agency_id' => auth()->user()->agency_id,
            'name' => $this->template_name,
            'body' => $this->template_body,
            'category' => $this->template_category,
            'status' => 'draft',
        ]);

        $this->reset(['template_name', 'template_body', 'showTemplateForm']);
        $this->dispatch('notify', message: 'Template saved.', type: 'success');
    }

    public function useTemplate(int $templateId): void
    {
        $template = WhatsAppTemplate::find($templateId);
        if ($template) {
            $this->compose_template_id = (string) $templateId;
            $this->compose_body = $template->body;
            $this->showCompose = true;
            $this->activeTab = 'messages';
        }
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $messages = WhatsAppMessage::with(['contact', 'template'])
            ->where('agency_id', $agencyId)
            ->latest()
            ->paginate(20);

        $templates = WhatsAppTemplate::where('agency_id', $agencyId)->latest()->get();

        $contacts = Contact::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'phone']);

        $stats = [
            'total_sent' => WhatsAppMessage::where('agency_id', $agencyId)->where('direction', 'outbound')->count(),
            'delivered' => WhatsAppMessage::where('agency_id', $agencyId)->where('status', 'delivered')->count(),
            'read' => WhatsAppMessage::where('agency_id', $agencyId)->where('status', 'read')->count(),
            'failed' => WhatsAppMessage::where('agency_id', $agencyId)->where('status', 'failed')->count(),
        ];

        return view('livewire.marketing.whatsapp-page', compact('messages', 'templates', 'contacts', 'stats'))
            ->layout('layouts.app');
    }
}
