<?php

namespace App\Http\Livewire\Marketing;

use App\Infrastructure\Persistence\Models\Campaign;
use App\Infrastructure\Persistence\Models\CampaignContent;
use Livewire\Component;
use Livewire\WithPagination;

class CampaignListPage extends Component
{
    use WithPagination;

    public string $filterStatus = '';
    public string $search = '';

    protected $queryString = [
        'filterStatus' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }

    public function updateStatus(int $campaignId, string $status)
    {
        $campaign = Campaign::find($campaignId);
        if ($campaign) {
            $campaign->update(['status' => $status]);
            $this->dispatch('notify', message: "Campaign status updated to {$status}.", type: 'success');
        }
    }

    public function deleteCampaign(int $campaignId)
    {
        Campaign::find($campaignId)?->delete();
        $this->dispatch('notify', message: 'Campaign deleted.', type: 'success');
    }

    public function render()
    {
        $campaigns = Campaign::with(['listing.property', 'contents'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate(12);

        $stats = [
            'total' => Campaign::count(),
            'active' => Campaign::where('status', 'active')->count(),
            'scheduled' => Campaign::where('status', 'scheduled')->count(),
            'completed' => Campaign::where('status', 'completed')->count(),
        ];

        return view('livewire.marketing.campaign-list-page', compact('campaigns', 'stats'))
            ->layout('layouts.app');
    }
}
