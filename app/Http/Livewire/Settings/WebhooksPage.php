<?php

namespace App\Http\Livewire\Settings;

use App\Infrastructure\Persistence\Models\WebhookSubscription;
use Livewire\Component;

class WebhooksPage extends Component
{
    public string $url    = '';
    public array  $events = [];
    public bool   $showForm  = false;
    public ?int   $deleteId  = null;

    public ?string $revealedSecret = null;

    public const AVAILABLE_EVENTS = [
        'listing.published'   => 'Listing Published',
        'listing.updated'     => 'Listing Updated',
        'listing.price_reduced' => 'Price Reduced',
        'listing.deleted'     => 'Listing Removed / Archived',
        'viewing.scheduled'   => 'Viewing Scheduled',
    ];

    public function addSubscription(): void
    {
        $this->guardPermission('agency.manage');

        $this->validate([
            'url'    => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'in:' . implode(',', array_keys(self::AVAILABLE_EVENTS)),
        ]);

        $sub = WebhookSubscription::register(
            auth()->user()->agency_id,
            $this->url,
            $this->events,
        );

        $this->revealedSecret = $sub->secret;
        $this->showForm  = false;
        $this->reset(['url', 'events']);

        $this->dispatch('notify', message: 'Webhook endpoint registered. Copy the secret now — it will not be shown again.', type: 'success');
    }

    public function deleteSubscription(int $id): void
    {
        $this->guardPermission('agency.manage');

        WebhookSubscription::where('id', $id)
            ->where('agency_id', auth()->user()->agency_id)
            ->delete();

        $this->deleteId       = null;
        $this->revealedSecret = null;

        $this->dispatch('notify', message: 'Webhook endpoint removed.', type: 'info');
    }

    public function toggleActive(int $id): void
    {
        $this->guardPermission('agency.manage');

        $sub = WebhookSubscription::where('id', $id)
            ->where('agency_id', auth()->user()->agency_id)
            ->firstOrFail();

        $sub->update(['is_active' => ! $sub->is_active, 'failure_count' => 0]);

        $this->dispatch('notify', message: $sub->is_active ? 'Webhook re-enabled.' : 'Webhook paused.', type: 'info');
    }

    public function dismissSecret(): void
    {
        $this->revealedSecret = null;
    }

    private function guardPermission(string $permission): void
    {
        if (! auth()->user()->hasPermissionTo($permission)) {
            $this->dispatch('notify', message: 'You do not have permission to do this.', type: 'error');
        }
    }

    public function render()
    {
        $subscriptions = WebhookSubscription::where('agency_id', auth()->user()->agency_id)
            ->latest()
            ->get();

        return view('livewire.settings.webhooks-page', [
            'subscriptions'   => $subscriptions,
            'availableEvents' => self::AVAILABLE_EVENTS,
        ])->layout('layouts.app');
    }
}
