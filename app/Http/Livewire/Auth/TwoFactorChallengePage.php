<?php

namespace App\Http\Livewire\Auth;

use Livewire\Component;
use OTPHP\TOTP;

class TwoFactorChallengePage extends Component
{
    public string $code = '';

    protected array $rules = [
        'code' => 'required|digits:6',
    ];

    public function submit(): void
    {
        $this->validate();

        $userId = session('2fa_user_id');
        if (!$userId) {
            redirect()->route('login');
            return;
        }

        $user = \App\Infrastructure\Persistence\Models\User::findOrFail($userId);
        $secret = decrypt($user->two_factor_secret);
        $totp = TOTP::createFromSecret($secret);

        if (!$totp->verify($this->code, null, 1)) {
            $this->addError('code', 'Invalid code. Please check your authenticator app.');
            return;
        }

        session()->forget('2fa_user_id');
        \Auth::login($user, session('2fa_remember', false));
        session()->forget('2fa_remember');

        setPermissionsTeamId($user->agency_id);

        redirect()->intended(route('dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.two-factor-challenge-page')
            ->layout('layouts.auth');
    }
}
