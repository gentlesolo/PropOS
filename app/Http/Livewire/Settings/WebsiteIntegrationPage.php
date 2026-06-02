<?php

namespace App\Http\Livewire\Settings;

use App\Infrastructure\Persistence\Models\Agency;
use Livewire\Component;

class WebsiteIntegrationPage extends Component
{
    public string $google_analytics_id   = '';
    public string $facebook_pixel_id     = '';
    public string $custom_header_scripts = '';
    public bool   $website_enabled       = true;
    public string $primary_color         = '#1E40AF';

    // Snippet builder state
    public string $snippet_city         = '';
    public string $snippet_mandate_type = '';
    public int    $snippet_per_page     = 9;
    public string $snippet_view_type    = 'grid';

    public function mount(): void
    {
        $agency = auth()->user()->agency;
        $settings = $agency->settings ?? [];

        $this->website_enabled       = (bool) ($settings['website_enabled']      ?? true);
        $this->google_analytics_id   = $settings['google_analytics_id']           ?? '';
        $this->facebook_pixel_id     = $settings['facebook_pixel_id']             ?? '';
        $this->custom_header_scripts = $settings['custom_header_scripts']         ?? '';
        $this->primary_color         = $agency->primary_color                     ?? '#1E40AF';
    }

    public function save(): void
    {
        $this->guardPermission('agency.manage');

        $this->validate([
            'google_analytics_id'   => 'nullable|string|max:50|regex:/^G-[A-Z0-9]+$/i',
            'facebook_pixel_id'     => 'nullable|string|max:30|regex:/^[0-9]+$/',
            'custom_header_scripts' => 'nullable|string|max:5000',
        ]);

        $agency    = auth()->user()->agency;
        $settings  = $agency->settings ?? [];

        $settings['website_enabled']       = $this->website_enabled;
        $settings['google_analytics_id']   = $this->google_analytics_id;
        $settings['facebook_pixel_id']     = $this->facebook_pixel_id;
        $settings['custom_header_scripts'] = $this->custom_header_scripts;

        $agency->update(['settings' => $settings]);

        $this->dispatch('notify', message: 'Website settings saved.', type: 'success');
    }

    private function guardPermission(string $permission): void
    {
        if (! auth()->user()->hasPermissionTo($permission)) {
            $this->dispatch('notify', message: 'You do not have permission to do this.', type: 'error');
        }
    }

    public function render()
    {
        $agency  = auth()->user()->agency;
        $apiKeys = $agency->apiKeys()->where('type', 'public_read')->latest()->get();

        return view('livewire.settings.website-integration-page', [
            'agency'  => $agency,
            'apiKeys' => $apiKeys,
        ])->layout('layouts.app');
    }
}
