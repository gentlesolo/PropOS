<?php

namespace App\Http\Livewire\Auth;

use App\Application\Identity\Actions\RegisterUserAction;
use App\Application\Identity\DTOs\RegisterUserData;
use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\TeamInvitation;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;

class RegisterPage extends Component
{
    // Invitation context (set when coming from an invite link)
    public ?string $invitationToken = null;
    public ?string $invitationAgencyName = null;
    public ?string $invitationRole = null;

    // New-agency fields (hidden in invite mode)
    public string $agency_name = '';
    public string $slug        = '';

    // Always present
    public string $first_name = '';
    public string $last_name  = '';
    public string $email      = '';
    public string $phone      = '';
    public string $password   = '';

    public function mount(): void
    {
        $token = session('invitation_token') ?? request()->query('invitation_token');

        if ($token) {
            $invitation = TeamInvitation::where('token', $token)
                ->whereNull('accepted_at')
                ->with('agency')
                ->first();

            if ($invitation) {
                $this->invitationToken     = $token;
                $this->invitationAgencyName = $invitation->agency?->name ?? 'the agency';
                $this->invitationRole       = $invitation->role;
                $this->email                = $invitation->email;
            }
        }
    }

    public function updatedAgencyName(): void
    {
        $this->slug = Str::slug($this->agency_name);
    }

    public function submit(RegisterUserAction $registerAction): mixed
    {
        // ── Invitation flow ────────────────────────────────────────────────
        if ($this->invitationToken) {
            $this->validate([
                'first_name' => 'required|string|max:255',
                'last_name'  => 'required|string|max:255',
                'email'      => 'required|email|max:255|unique:users,email',
                'phone'      => 'nullable|string|max:20',
                'password'   => 'required|string|min:8',
            ]);

            $invitation = TeamInvitation::where('token', $this->invitationToken)
                ->whereNull('accepted_at')
                ->firstOrFail();

            setPermissionsTeamId($invitation->agency_id);

            $user = User::create([
                'agency_id'          => $invitation->agency_id,
                'first_name'         => $this->first_name,
                'last_name'          => $this->last_name,
                'email'              => $this->email,
                'phone'              => $this->phone ?: null,
                'password'           => Hash::make($this->password),
                'status'             => 'active',
                'email_verified_at'  => now(),
            ]);

            $user->assignRole($invitation->role);
            $invitation->update(['accepted_at' => now()]);

            Auth::login($user);
            session()->forget('invitation_token');

            return redirect()->route('dashboard')->with('success', "Welcome to {$this->invitationAgencyName}!");
        }

        // ── New-agency flow ───────────────────────────────────────────────
        $this->validate([
            'agency_name' => 'required|string|max:255',
            'slug'        => 'required|string|alpha_dash|max:255|unique:agencies,slug',
            'first_name'  => 'required|string|max:255',
            'last_name'   => 'required|string|max:255',
            'email'       => 'required|email|max:255|unique:users,email',
            'phone'       => 'nullable|string|max:20',
            'password'    => 'required|string|min:8',
        ]);

        $dto = RegisterUserData::fromArray([
            'agency_name' => $this->agency_name,
            'slug'        => $this->slug,
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'password'    => $this->password,
        ]);

        $user = $registerAction->execute($dto);

        Auth::login($user);
        setPermissionsTeamId($user->agency_id);

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.auth.register-page')
            ->layout('layouts.auth');
    }
}
