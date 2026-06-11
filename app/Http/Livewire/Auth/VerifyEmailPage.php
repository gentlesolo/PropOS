<?php

namespace App\Http\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class VerifyEmailPage extends Component
{
    public bool $verificationLinkSent = false;

    public function resend()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        Auth::user()->sendEmailVerificationNotification();

        $this->verificationLinkSent = true;
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function render()
    {
        return view('livewire.auth.verify-email-page')
            ->layout('layouts.auth');
    }
}
