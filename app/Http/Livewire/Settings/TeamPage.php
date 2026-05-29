<?php

namespace App\Http\Livewire\Settings;

use App\Infrastructure\Notifications\TeamInvitationNotification;
use App\Infrastructure\Persistence\Models\TeamInvitation;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Component;

class TeamPage extends Component
{
    // Invite form
    public bool $showInviteForm = false;
    public string $invite_email = '';
    public string $invite_role  = 'agent';

    // Role change inline
    public array $memberRoles = [];   // [userId => roleName]

    // Confirm deactivation
    public ?int $confirmDeactivateId = null;

    public function mount(): void
    {
        $members = $this->agencyUsers();
        foreach ($members as $user) {
            $this->memberRoles[$user->id] = $user->roles->first()?->name ?? 'agent';
        }
    }

    // ─── Invite ──────────────────────────────────────────────────────────────

    public function sendInvitation(): void
    {
        $this->guardPermission('agency.manage');

        $this->validate([
            'invite_email' => 'required|email|max:255',
            'invite_role'  => 'required|in:agent,senior_agent,branch_manager,marketing_manager,admin,principal',
        ]);

        $agencyId = auth()->user()->agency_id;

        // Guard: already a member
        if (User::where('email', $this->invite_email)->where('agency_id', $agencyId)->exists()) {
            $this->addError('invite_email', 'This person is already a team member.');
            return;
        }

        // Guard: pending invite already exists
        if (TeamInvitation::where('email', $this->invite_email)
                ->where('agency_id', $agencyId)
                ->whereNull('accepted_at')
                ->exists()) {
            $this->addError('invite_email', 'An invitation has already been sent to this address.');
            return;
        }

        $invitation = TeamInvitation::create([
            'agency_id'  => $agencyId,
            'email'      => $this->invite_email,
            'role'       => $this->invite_role,
            'token'      => Str::random(64),
            'invited_by' => auth()->id(),
        ]);

        Notification::route('mail', $this->invite_email)
            ->notify(new TeamInvitationNotification($invitation));

        $this->reset(['invite_email', 'showInviteForm']);
        $this->dispatch('notify', message: "Invitation sent to {$invitation->email}.", type: 'success');
    }

    public function resendInvitation(int $invitationId): void
    {
        $this->guardPermission('agency.manage');

        $invitation = TeamInvitation::where('id', $invitationId)
            ->where('agency_id', auth()->user()->agency_id)
            ->firstOrFail();

        // Refresh token so the link is fresh
        $invitation->update(['token' => Str::random(64)]);

        Notification::route('mail', $invitation->email)
            ->notify(new TeamInvitationNotification($invitation->fresh()));

        $this->dispatch('notify', message: "Invitation resent to {$invitation->email}.", type: 'success');
    }

    public function revokeInvitation(int $invitationId): void
    {
        $this->guardPermission('agency.manage');

        TeamInvitation::where('id', $invitationId)
            ->where('agency_id', auth()->user()->agency_id)
            ->delete();

        $this->dispatch('notify', message: 'Invitation revoked.', type: 'info');
    }

    // ─── Role management ─────────────────────────────────────────────────────

    public function changeRole(int $userId): void
    {
        $this->guardPermission('agency.manage');

        $newRole = $this->memberRoles[$userId] ?? null;

        if (! $newRole) {
            return;
        }

        $validRoles = ['agent', 'senior_agent', 'branch_manager', 'marketing_manager', 'admin', 'principal'];
        if (! in_array($newRole, $validRoles)) {
            $this->addError("memberRoles.{$userId}", 'Invalid role selected.');
            return;
        }

        // Prevent self-demotion from principal if the only one
        $user = User::where('id', $userId)->where('agency_id', auth()->user()->agency_id)->firstOrFail();

        if ($userId === auth()->id() && $newRole !== 'principal') {
            $principalCount = $this->agencyUsers()
                ->filter(fn($u) => $u->hasRole('principal'))
                ->count();

            if ($principalCount <= 1) {
                $this->dispatch('notify', message: 'You cannot remove the last principal.', type: 'error');
                $this->memberRoles[$userId] = 'principal';
                return;
            }
        }

        $user->syncRoles([$newRole]);

        $this->dispatch('notify', message: "{$user->name}'s role updated to " . ucfirst(str_replace('_', ' ', $newRole)) . '.', type: 'success');
    }

    // ─── Status management ───────────────────────────────────────────────────

    public function deactivateMember(int $userId): void
    {
        $this->guardPermission('agency.manage');

        if ($userId === auth()->id()) {
            $this->dispatch('notify', message: 'You cannot deactivate your own account.', type: 'error');
            return;
        }

        User::where('id', $userId)->where('agency_id', auth()->user()->agency_id)
            ->update(['status' => 'suspended']);

        $this->confirmDeactivateId = null;
        $this->dispatch('notify', message: 'Team member deactivated.', type: 'info');
    }

    public function reactivateMember(int $userId): void
    {
        $this->guardPermission('agency.manage');

        User::where('id', $userId)->where('agency_id', auth()->user()->agency_id)
            ->update(['status' => 'active']);

        $this->dispatch('notify', message: 'Team member reactivated.', type: 'success');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function agencyUsers()
    {
        return User::where('agency_id', auth()->user()->agency_id)
            ->with('roles')
            ->orderBy('first_name')
            ->get();
    }

    private function guardPermission(string $permission): void
    {
        if (! auth()->user()->hasPermissionTo($permission)) {
            $this->dispatch('notify', message: 'You do not have permission to do this.', type: 'error');
            $this->js('window.location.href="/"');
        }
    }

    public function render()
    {
        $agencyId = auth()->user()->agency_id;

        $members = $this->agencyUsers();

        $pendingInvitations = TeamInvitation::where('agency_id', $agencyId)
            ->whereNull('accepted_at')
            ->with('inviter')
            ->latest()
            ->get();

        $availableRoles = [
            'agent'             => 'Agent',
            'senior_agent'      => 'Senior Agent',
            'branch_manager'    => 'Branch Manager',
            'marketing_manager' => 'Marketing Manager',
            'admin'             => 'Admin / PA',
            'principal'         => 'Principal',
        ];

        return view('livewire.settings.team-page', compact('members', 'pendingInvitations', 'availableRoles'))
            ->layout('layouts.app');
    }
}
