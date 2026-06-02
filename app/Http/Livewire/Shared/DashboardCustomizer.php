<?php

namespace App\Http\Livewire\Shared;

use Livewire\Component;

class DashboardCustomizer extends Component
{
    public array $enabledWidgets = [];
    public array $allWidgets     = [];
    public bool  $open           = false;

    private const LABELS = [
        'pipeline'               => 'Pipeline Value',
        'active_listings'        => 'Active Listings',
        'new_leads'              => 'New Leads',
        'hot_buyers'             => 'Hot Buyers',
        'occupancy_rate'         => 'Occupancy Rate',
        'maintenance_efficiency' => 'Maintenance Efficiency',
        'compliance_overdue'     => 'Compliance Status',
    ];

    public function mount(array $enabledWidgets, array $allWidgets): void
    {
        $this->enabledWidgets = $enabledWidgets;
        $this->allWidgets     = $allWidgets;
    }

    public function toggle(string $widget): void
    {
        if (in_array($widget, $this->enabledWidgets)) {
            $this->enabledWidgets = array_values(array_filter(
                $this->enabledWidgets,
                fn ($w) => $w !== $widget
            ));
        } else {
            $this->enabledWidgets[] = $widget;
        }
    }

    public function save(): void
    {
        auth()->user()->update(['dashboard_widgets' => $this->enabledWidgets]);
        $this->redirect(route('dashboard'));
    }

    public function render()
    {
        return view('livewire.shared.dashboard-customizer', [
            'labels' => self::LABELS,
        ]);
    }
}
