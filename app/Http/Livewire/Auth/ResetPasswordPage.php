<?php

namespace App\Http\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class ResetPasswordPage extends Component
{
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $isSuccessful = false;

    public function mount($token)
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    protected array $rules = [
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ];

    public function resetPassword()
    {
        $this->validate();

        $status = Password::broker()->reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->setRememberToken(Str::random(60));
                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            $this->isSuccessful = true;
            return;
        }

        $this->addError('email', __($status));
    }

    public function render()
    {
        return view('livewire.auth.reset-password-page')
            ->layout('layouts.auth');
    }
}
