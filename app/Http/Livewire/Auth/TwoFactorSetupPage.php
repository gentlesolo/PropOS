<?php

namespace App\Http\Livewire\Auth;

use Livewire\Component;
use OTPHP\TOTP;

class TwoFactorSetupPage extends Component
{
    public string $code = '';
    public bool $enabled;
    public ?string $qrCodeUrl = null;
    public ?string $secret = null;
    public bool $showDisableConfirm = false;
    public string $disable_password = '';

    public function mount()
    {
        $user = auth()->user();
        $this->enabled = (bool) $user->two_factor_enabled;

        if (!$this->enabled) {
            $this->generateSecret();
        }
    }

    public function generateSecret(): void
    {
        $totp = TOTP::generate();
        $totp->setLabel(auth()->user()->email);
        $totp->setIssuer(config('app.name', 'PropOS'));

        $this->secret = $totp->getSecret();
        $this->qrCodeUrl = $totp->getProvisioningUri();

        session(['2fa_pending_secret' => $this->secret]);
    }

    public function enable(): void
    {
        $this->validate(['code' => 'required|digits:6']);

        $secret = session('2fa_pending_secret');
        if (!$secret) {
            $this->addError('code', 'Session expired. Please refresh the page.');
            return;
        }

        $totp = TOTP::createFromSecret($secret);

        if (!$totp->verify($this->code, null, 1)) {
            $this->addError('code', 'Invalid code. Please try again.');
            return;
        }

        auth()->user()->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt($secret),
        ]);

        session()->forget('2fa_pending_secret');
        $this->enabled = true;
        $this->secret = null;
        $this->qrCodeUrl = null;
        $this->code = '';
    }

    public function disable(): void
    {
        $this->validate(['disable_password' => 'required']);

        if (!\Hash::check($this->disable_password, auth()->user()->password)) {
            $this->addError('disable_password', 'Password is incorrect.');
            return;
        }

        auth()->user()->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
        ]);

        $this->enabled = false;
        $this->showDisableConfirm = false;
        $this->disable_password = '';
        $this->generateSecret();
    }

    public function render()
    {
        return view('livewire.auth.two-factor-setup-page')
            ->layout('layouts.app');
    }
}
