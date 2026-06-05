<?php

namespace App\Http\Livewire\Settings;

use Livewire\Component;
use Livewire\WithFileUploads;

class ProfilePage extends Component
{
    use WithFileUploads;

    public string $activeTab = 'profile';

    // Profile fields
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $job_title = '';
    public string $bio = '';
    public $avatar;

    // Password fields
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    // Agency fields
    public string $agency_name    = '';
    public string $agency_phone   = '';
    public string $agency_email   = '';
    public string $agency_website = '';
    public string $agency_address = '';
    public string $timezone       = '';
    public string $currency       = '';
    public string $primary_color   = '';
    public string $secondary_color = '';
    public string $accent_color    = '';
    public string $font_family     = '';
    public string $border_radius   = 'default';
    public string $sidebar_style   = 'dark';
    public string $custom_css      = '';
    public bool   $use_platform_branding = false;
    public $agency_logo;
    public $favicon;

    // Commission split configuration
    public string $default_commission_rate  = '5';
    public string $split_agent_pct          = '50';
    public string $split_principal_pct      = '40';
    public string $split_referral_pct       = '10';

    // Team (kept for backward compat — full management is on /settings/team)
    public string $invite_email = '';
    public string $invite_role  = 'agent';
    public bool $showInviteForm = false;

    public function mount()
    {
        $user = auth()->user();
        $this->fill([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'job_title' => $user->job_title ?? '',
            'bio' => $user->bio ?? '',
        ]);

        if ($user->agency) {
            $agency  = $user->agency;
            $splits  = $agency->commission_splits ?? [];
            $this->fill([
                'agency_name'    => $agency->name,
                'agency_phone'   => $agency->phone ?? '',
                'agency_email'   => $agency->email ?? '',
                'agency_website' => $agency->website ?? '',
                'agency_address' => $agency->address ?? '',
                'timezone'       => $agency->timezone ?? 'UTC',
                'currency'       => $agency->currency ?? 'NGN',
                'primary_color'   => $agency->primary_color ?? '#1E40AF',
                'secondary_color' => $agency->secondary_color ?? '#18181B',
                'accent_color'    => $agency->accent_color ?? '#F59E0B',
                'font_family'          => $agency->font_family ?? '',
                'border_radius'        => $agency->border_radius ?? 'default',
                'sidebar_style'        => $agency->sidebar_style ?? 'dark',
                'custom_css'           => $agency->custom_css ?? '',
                'use_platform_branding' => (bool) ($agency->use_platform_branding ?? false),
                'default_commission_rate' => (string) ($agency->default_commission_rate ?? '5'),
                'split_agent_pct'      => (string) ($splits['agent'] ?? '50'),
                'split_principal_pct'  => (string) ($splits['principal'] ?? '40'),
                'split_referral_pct'   => (string) ($splits['referral'] ?? '10'),
            ]);
        }
    }

    public function saveProfile()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
            'phone' => 'nullable|string|max:50',
            'job_title' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $data = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'job_title' => $this->job_title ?: null,
            'bio' => $this->bio ?: null,
        ];

        if ($this->avatar) {
            $data['avatar_path'] = $this->avatar->store('avatars', 'public');
            $this->avatar = null;
        }

        auth()->user()->update($data);
        session()->flash('profile_saved', 'Profile updated successfully.');
    }

    public function savePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if (!\Hash::check($this->current_password, auth()->user()->password)) {
            $this->addError('current_password', 'Current password is incorrect.');
            return;
        }

        auth()->user()->update(['password' => bcrypt($this->new_password)]);
        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->flash('password_saved', 'Password updated successfully.');
    }

    public function saveAgency()
    {
        $this->validate([
            'agency_name'    => 'required|string|max:255',
            'agency_email'   => 'required|email|max:255',
            'agency_phone'   => 'nullable|string|max:50',
            'agency_website' => 'nullable|url|max:255',
            'agency_address' => 'nullable|string|max:500',
            'timezone'       => 'required|string',
            'currency'       => 'required|string|max:3',
            'primary_color'   => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color'    => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_family'     => 'nullable|string|in:,Inter,Poppins,Lato,Roboto',
            'border_radius'   => 'required|string|in:sharp,default,rounded,pill',
            'sidebar_style'   => 'required|string|in:dark,light,brand',
            'custom_css'           => 'nullable|string|max:10000',
            'use_platform_branding' => 'boolean',
            'agency_logo'     => 'nullable|image|max:2048',
            'favicon'         => 'nullable|image|max:512',
        ]);

        $data = [
            'name'           => $this->agency_name,
            'email'          => $this->agency_email,
            'phone'          => $this->agency_phone ?: null,
            'website'        => $this->agency_website ?: null,
            'address'        => $this->agency_address ?: null,
            'timezone'       => $this->timezone,
            'currency'       => $this->currency,
            'primary_color'   => $this->primary_color,
            'secondary_color' => $this->secondary_color ?: null,
            'accent_color'    => $this->accent_color ?: null,
            'font_family'     => $this->font_family ?: null,
            'border_radius'   => $this->border_radius,
            'sidebar_style'   => $this->sidebar_style,
            'custom_css'           => $this->custom_css ?: null,
            'use_platform_branding' => $this->use_platform_branding,
        ];

        if ($this->agency_logo) {
            $data['logo_path'] = $this->agency_logo->store('agency-logos', 'public');
            $this->agency_logo = null;
        }

        if ($this->favicon) {
            $data['favicon_path'] = $this->favicon->store('agency-favicons', 'public');
            $this->favicon = null;
        }

        auth()->user()->agency->update($data);
        session()->flash('agency_saved', 'Agency settings updated successfully.');
    }

    public function sendInvitation()
    {
        $this->validate([
            'invite_email' => 'required|email|max:255',
            'invite_role'  => 'required|in:agent,senior_agent,branch_manager,marketing_manager,admin,principal',
        ]);

        $existing = \App\Infrastructure\Persistence\Models\TeamInvitation::where('email', $this->invite_email)
            ->where('agency_id', auth()->user()->agency_id)
            ->whereNull('accepted_at')
            ->first();

        if ($existing) {
            $this->addError('invite_email', 'An invitation has already been sent to this email.');
            return;
        }

        $invitation = \App\Infrastructure\Persistence\Models\TeamInvitation::create([
            'agency_id' => auth()->user()->agency_id,
            'email' => $this->invite_email,
            'role' => $this->invite_role,
            'token' => \Str::random(64),
            'invited_by' => auth()->id(),
        ]);

        \Notification::route('mail', $this->invite_email)
            ->notify(new \App\Infrastructure\Notifications\TeamInvitationNotification($invitation));

        $this->reset(['invite_email', 'showInviteForm']);
        session()->flash('invite_sent', "Invitation sent to {$this->invite_email}.");
    }

    public function revokeInvitation(int $invitationId)
    {
        \App\Infrastructure\Persistence\Models\TeamInvitation::where('id', $invitationId)
            ->where('agency_id', auth()->user()->agency_id)
            ->delete();
    }

    public function saveCommissionSplits(): void
    {
        $this->validate([
            'default_commission_rate' => 'required|numeric|min:0|max:100',
            'split_agent_pct'         => 'required|numeric|min:0|max:100',
            'split_principal_pct'     => 'required|numeric|min:0|max:100',
            'split_referral_pct'      => 'required|numeric|min:0|max:100',
        ]);

        $total = (float) $this->split_agent_pct + (float) $this->split_principal_pct + (float) $this->split_referral_pct;
        if (abs($total - 100) > 0.01) {
            $this->addError('split_agent_pct', "Split percentages must add up to 100% (currently {$total}%).");
            return;
        }

        auth()->user()->agency->update([
            'default_commission_rate' => $this->default_commission_rate,
            'commission_splits' => [
                'agent'     => (float) $this->split_agent_pct,
                'principal' => (float) $this->split_principal_pct,
                'referral'  => (float) $this->split_referral_pct,
            ],
        ]);

        session()->flash('commission_saved', 'Commission split configuration saved.');
    }

    public function runMlsSyncJob()
    {
        \App\Infrastructure\Queue\Jobs\SyncMlsListingsJob::dispatch();
        session()->flash('mls_sync_triggered', 'Background MLS sync job dispatched successfully!');
    }

    public function togglePlatformBranding(): void
    {
        $this->use_platform_branding = !$this->use_platform_branding;
        auth()->user()->agency->update([
            'use_platform_branding' => $this->use_platform_branding,
        ]);
    }

    public function render()
    {
        $teamMembers = auth()->user()->agency?->users()->with('roles')->get() ?? collect();
        $pendingInvitations = \App\Infrastructure\Persistence\Models\TeamInvitation::where('agency_id', auth()->user()->agency_id)
            ->whereNull('accepted_at')
            ->latest()
            ->get();

        return view('livewire.settings.profile-page', compact('teamMembers', 'pendingInvitations'))
            ->layout('layouts.app');
    }
}
