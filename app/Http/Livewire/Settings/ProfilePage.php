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
    public string $agency_name = '';
    public string $agency_phone = '';
    public string $agency_email = '';
    public string $agency_website = '';
    public string $agency_address = '';
    public string $timezone = '';
    public string $currency = '';
    public string $primary_color = '';
    public $agency_logo;

    // Team
    public string $invite_email = '';
    public string $invite_role = 'agent';
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
            $agency = $user->agency;
            $this->fill([
                'agency_name' => $agency->name,
                'agency_phone' => $agency->phone ?? '',
                'agency_email' => $agency->email ?? '',
                'agency_website' => $agency->website ?? '',
                'agency_address' => $agency->address ?? '',
                'timezone' => $agency->timezone ?? 'UTC',
                'currency' => $agency->currency ?? 'NGN',
                'primary_color' => $agency->primary_color ?? '#1E40AF',
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
            'agency_name' => 'required|string|max:255',
            'agency_email' => 'required|email|max:255',
            'agency_phone' => 'nullable|string|max:50',
            'agency_website' => 'nullable|url|max:255',
            'agency_address' => 'nullable|string|max:500',
            'timezone' => 'required|string',
            'currency' => 'required|string|max:3',
            'primary_color' => 'required|string|max:7',
            'agency_logo' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name' => $this->agency_name,
            'email' => $this->agency_email,
            'phone' => $this->agency_phone ?: null,
            'website' => $this->agency_website ?: null,
            'address' => $this->agency_address ?: null,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'primary_color' => $this->primary_color,
        ];

        if ($this->agency_logo) {
            $data['logo_path'] = $this->agency_logo->store('agency-logos', 'public');
            $this->agency_logo = null;
        }

        auth()->user()->agency->update($data);
        session()->flash('agency_saved', 'Agency settings updated successfully.');
    }

    public function sendInvitation()
    {
        $this->validate([
            'invite_email' => 'required|email|max:255',
            'invite_role' => 'required|in:principal,agent,admin,viewer',
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
