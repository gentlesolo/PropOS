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
    public string $country     = 'NG';
    public string $size        = '1-5';
    public string $subscription_plan = 'solo';
    public string $billing_cycle = 'monthly';

    // Always present
    public string $first_name = '';
    public string $last_name  = '';
    public string $email      = '';
    public string $phone      = '';
    public string $role       = 'principal';
    public string $password   = '';
    public bool $agree_to_terms = false;

    public int $step = 1;

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
                $this->role                 = $invitation->role;
                $this->step                 = 2; // Skip step 1 if invited
            }
        }
    }

    public function updatedAgencyName(): void
    {
        $this->slug = Str::slug($this->agency_name);
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'agency_name' => 'required|string|max:255',
                'slug'        => 'required|string|alpha_dash|max:255|unique:agencies,slug',
                'country'     => 'required|string|in:NG,ZA,KE,GH',
                'size'        => 'required|string',
            ]);
            $this->step = 2;
        } elseif ($this->step === 2) {
            // If invitation mode, email is prefilled so we don't validate unique:users unless it's changed
            $emailRule = $this->invitationToken ? 'required|email|max:255' : 'required|email|max:255|unique:users,email';
            $this->validate([
                'first_name' => 'required|string|max:255',
                'last_name'  => 'required|string|max:255',
                'email'      => $emailRule,
                'phone'      => 'nullable|string|max:20',
                'role'       => 'required|string',
            ]);
            $this->step = 3;
        } elseif ($this->step === 3 && !$this->invitationToken) {
            $this->validate([
                'subscription_plan' => 'required|in:solo,agency_pro,enterprise',
                'billing_cycle'     => 'required|in:monthly,annual',
            ]);
            $this->step = 4;
        } elseif ($this->step === 3 && $this->invitationToken) {
            // Invitations don't pick plans
            $this->step = 4;
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            // Cannot go back to step 1 in invitation mode
            if ($this->invitationToken && $this->step === 2) {
                return;
            }
            $this->step--;
        }
    }

    public function submit(RegisterUserAction $registerAction): mixed
    {
        // ── Invitation flow ────────────────────────────────────────────────
        if ($this->invitationToken) {
            $this->validate([
                'first_name'     => 'required|string|max:255',
                'last_name'      => 'required|string|max:255',
                'email'          => 'required|email|max:255|unique:users,email',
                'phone'          => 'nullable|string|max:20',
                'password'       => 'required|string|min:8',
                'agree_to_terms' => 'accepted',
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
                'job_title'          => $this->role,
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
            'agency_name'    => 'required|string|max:255',
            'slug'           => 'required|string|alpha_dash|max:255|unique:agencies,slug',
            'country'        => 'required|string|in:NG,ZA,KE,GH',
            'size'           => 'required|string',
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'email'          => 'required|email|max:255|unique:users,email',
            'phone'          => 'nullable|string|max:20',
            'role'           => 'required|string',
            'password'       => 'required|string|min:8',
            'agree_to_terms' => 'accepted',
        ]);

        $dto = RegisterUserData::fromArray([
            'agency_name' => $this->agency_name,
            'slug'        => $this->slug,
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'password'    => $this->password,
            'country'     => $this->country,
            'size'        => $this->size,
            'role'        => $this->role,
            'subscription_plan' => $this->subscription_plan,
            'billing_cycle' => $this->billing_cycle,
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
