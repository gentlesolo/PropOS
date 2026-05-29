<?php

namespace App\Http\Livewire\Shared;

use App\Infrastructure\Tenancy\TenantResolver;
use Livewire\Component;

class Sidebar extends Component
{
    public function render()
    {
        $resolver = app(TenantResolver::class);
        $agency = $resolver->getCurrentAgency();
        $user = auth()->user();

        // Default navigation links
        $menuItems = [
            ['title' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'permission' => 'dashboard.view'],
            ['title' => 'Contacts', 'route' => 'crm.contacts', 'icon' => 'users', 'permission' => 'contacts.view_own'],
            ['title' => 'Pipeline', 'route' => 'crm.pipeline', 'icon' => 'chart-bar', 'permission' => 'contacts.view_own'],
            ['title' => 'Listings', 'route' => 'listing.index', 'icon' => 'home-modern', 'permission' => 'listings.view_own'],
            ['title' => 'Marketing', 'route' => 'marketing.campaign.new', 'icon' => 'megaphone', 'permission' => 'listings.view_own'],
            ['title' => 'Viewings', 'route' => 'viewing.day', 'icon' => 'map', 'permission' => 'dashboard.view'],
            ['title' => 'Scorecard', 'route' => 'analytics.scorecard', 'icon' => 'chart-pie', 'permission' => 'dashboard.view'],
            ['title' => 'Listing Health', 'route' => 'analytics.listing-health', 'icon' => 'heart', 'permission' => 'dashboard.view'],
            ['title' => 'Forecast', 'route' => 'analytics.forecast', 'icon' => 'arrow-trending-up', 'permission' => 'dashboard.view'],
            ['title' => 'Compliance', 'route' => 'compliance.transactions', 'icon' => 'shield-check', 'permission' => 'dashboard.view'],
            ['title' => 'Commissions', 'route' => 'finance.commissions', 'icon' => 'banknotes', 'permission' => 'dashboard.view'],
            ['title' => 'Skills Library', 'route' => 'training.skills-library', 'icon' => 'academic-cap', 'permission' => 'dashboard.view'],
            ['title' => 'AI Role-Play', 'route' => 'training.role-play', 'icon' => 'users', 'permission' => 'dashboard.view'],
            ['title' => 'AI Planner', 'route' => 'ai.planner', 'icon' => 'sparkles', 'permission' => 'dashboard.view'],
            ['title' => 'Settings', 'route' => 'settings.profile', 'icon' => 'cog', 'permission' => 'agency.view'],
        ];

        // Filter menu items by user permissions
        if ($user) {
            $menuItems = array_filter($menuItems, function ($item) use ($user) {
                return $user->hasPermissionTo($item['permission']);
            });
        }

        return view('livewire.shared.sidebar', [
            'agency' => $agency,
            'menuItems' => $menuItems,
        ]);
    }
}
